<?php

namespace App\Imports;

use App\Models\DrugClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrugClassImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
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
                $name = $row['drug_class_name'] ?? $row['drug class name'] ?? null;
                
                if (empty($name)) {
                    $this->errors[] = "Row {$rowNumber}: Drug class name is empty";
                    $this->errorCount++;
                    continue;
                }

                $data[] = [
                    'drug_class_name' => $name,
                    'slug' => Str::slug($name . '-' . uniqid()),
                    'description' => $row['description'] ?? null,
                    'generic_count' => (int) ($row['generic_count'] ?? $row['generics count'] ?? 0),
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
                Log::error("DrugClass Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("DrugClass Import completed: {$this->successCount} success, {$this->errorCount} errors");
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('drug_classes')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'drug_class_name' => 'required|string|max:255',
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