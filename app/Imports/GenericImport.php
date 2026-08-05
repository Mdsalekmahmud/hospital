<?php

namespace App\Imports;

use App\Models\Generic;
use App\Models\DrugClass;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenericImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private array $errors = [];
    private int $successCount = 0;
    private int $errorCount = 0;
    private array $drugClassMap = [];

    public function __construct()
    {
        $this->drugClassMap = DrugClass::pluck('drug_class_id', 'drug_class_name')->toArray();
    }

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
                $name = $row['generic_name'] ?? $row['generic name'] ?? null;
                
                if (empty($name)) {
                    $this->errors[] = "Row {$rowNumber}: Generic name is empty";
                    $this->errorCount++;
                    continue;
                }

                // Get drug class ID
                $drugClassId = null;
                $drugClassName = $row['drug_class'] ?? $row['drug class'] ?? null;
                if ($drugClassName) {
                    $drugClassId = $this->getDrugClassId($drugClassName);
                }

                $data[] = [
                    'generic_name' => $name,
                    'slug' => Str::slug($name . '-' . uniqid()),
                    'drug_class_id' => $drugClassId,
                    'strength' => $row['strength'] ?? null,
                    'unit' => $row['unit'] ?? null,
                    'indication' => $row['indication'] ?? null,
                    'contraindication' => $row['contraindication'] ?? $row['contraindications'] ?? null,
                    'side_effects' => $row['side_effects'] ?? $row['side effects'] ?? null,
                    'pharmacology' => $row['pharmacology'] ?? $row['pharmacology description'] ?? null,
                    'dosage' => $row['dosage'] ?? $row['dosage description'] ?? null,
                    'interaction' => $row['interaction'] ?? $row['interaction description'] ?? null,
                    'precautions' => $row['precautions'] ?? $row['precautions description'] ?? null,
                    'pregnancy_lactation' => $row['pregnancy_lactation'] ?? $row['pregnancy and lactation description'] ?? null,
                    'pediatric_usage' => $row['pediatric_usage'] ?? $row['pediatric usage description'] ?? null,
                    'overdose_effects' => $row['overdose_effects'] ?? $row['overdose effects description'] ?? null,
                    'storage_conditions' => $row['storage_conditions'] ?? $row['storage conditions description'] ?? null,
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
                Log::error("Generic Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("Generic Import completed: {$this->successCount} success, {$this->errorCount} errors");
    }

    private function getDrugClassId(string $name): ?int
    {
        if (isset($this->drugClassMap[$name])) {
            return $this->drugClassMap[$name];
        }

        try {
            $drugClass = DrugClass::create([
                'drug_class_name' => $name,
                'slug' => Str::slug($name . '-' . uniqid()),
                'is_active' => true
            ]);
            $this->drugClassMap[$name] = $drugClass->drug_class_id;
            return $drugClass->drug_class_id;
        } catch (\Exception $e) {
            Log::error("Failed to create drug class: {$name} - " . $e->getMessage());
            return null;
        }
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('generics')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'generic_name' => 'required|string|max:255',
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