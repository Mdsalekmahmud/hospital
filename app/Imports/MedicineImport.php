<?php

namespace App\Imports;

use App\Models\Medicine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicineImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private array $errors = [];
    private int $successCount = 0;
    private int $errorCount = 0;

    public function collection(Collection $rows)
    {
        $this->successCount = 0;
        $this->errorCount = 0;
        
        $data = [];
        $batchSize = 500;
        $rowNumber = 0;

        foreach ($rows as $row) {
            $rowNumber++;
            
            try {
                $brandName = trim($row['brand name'] ?? $row['brand_name'] ?? '');
                
                if (empty($brandName)) {
                    $this->errors[] = "Row {$rowNumber}: Brand name is empty";
                    $this->errorCount++;
                    continue;
                }

                // Clean price from package info
                $packageContainer = $this->cleanPackageData($row['package container'] ?? $row['package_container'] ?? null);
                $packageSize = $this->cleanPackageData($row['package size'] ?? $row['package_size'] ?? null);

                $data[] = [
                    'brand_id' => $row['brand id'] ?? $row['brand_id'] ?? null,
                    'brand_name' => $brandName,
                    'type' => $row['type'] ?? 'allopathic',
                    'slug' => Str::slug($brandName . '-' . uniqid()),
                    'dosage_form' => $row['dosage form'] ?? $row['dosage_form'] ?? null,
                    'generic' => $row['generic'] ?? null,
                    'strength' => $row['strength'] ?? null,
                    'manufacturer' => $row['manufacturer'] ?? null,
                    'package_container' => $packageContainer,
                    'package_size' => $packageSize,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if (count($data) >= $batchSize) {
                    $this->bulkInsert($data);
                    $data = [];
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                $this->errorCount++;
                Log::error("Medicine Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("Medicine Import completed: {$this->successCount} success, {$this->errorCount} errors");
        
        if (!empty($this->errors)) {
            Log::warning("Medicine Import Errors: " . implode("\n", array_slice($this->errors, 0, 20)));
        }
    }

    private function cleanPackageData(?string $data): ?string
    {
        if (empty($data)) {
            return null;
        }
        
        // Remove extra quotes and clean
        $data = trim($data);
        $data = str_replace(['"', "'"], '', $data);
        
        // Remove duplicate comma patterns
        $data = preg_replace('/,+$/', '', $data);
        $data = preg_replace('/,{2,}/', ',', $data);
        
        return $data;
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('medicines')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'brand_name' => 'nullable|string|max:255',
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }
}