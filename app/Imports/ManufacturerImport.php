<?php

namespace App\Imports;

use App\Models\Manufacturer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManufacturerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
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
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            
            try {
                $name = $row['manufacturer_name'] ?? $row['manufacturer name'] ?? null;
                
                if (empty($name)) {
                    $this->errors[] = "Row {$rowNumber}: Manufacturer name is empty";
                    $this->errorCount++;
                    continue;
                }

                $data[] = [
                    'manufacturer_name' => $name,
                    'slug' => Str::slug($name . '-' . uniqid()),
                    'country' => $row['country'] ?? null,
                    'address' => $row['address'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'website' => $row['website'] ?? null,
                    'brand_count' => 0,
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
                Log::error("Manufacturer Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("Manufacturer Import completed: {$this->successCount} success, {$this->errorCount} errors");
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('manufacturers')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'manufacturer_name' => 'required|string|max:255',
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