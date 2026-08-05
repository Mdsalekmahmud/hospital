<?php

namespace App\Imports;

use App\Models\Indication;
use App\Models\Generic;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndicationImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private array $errors = [];
    private int $successCount = 0;
    private int $errorCount = 0;
    private array $genericMap = [];

    public function __construct()
    {
        $this->genericMap = Generic::pluck('generic_id', 'generic_name')->toArray();
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
                $name = $row['indication_name'] ?? $row['indication name'] ?? null;
                
                if (empty($name)) {
                    $this->errors[] = "Row {$rowNumber}: Indication name is empty";
                    $this->errorCount++;
                    continue;
                }

                // Get Generic ID
                $genericId = null;
                $genericName = $row['generic_name'] ?? $row['generic name'] ?? null;
                if ($genericName) {
                    if (isset($this->genericMap[$genericName])) {
                        $genericId = $this->genericMap[$genericName];
                    } else {
                        $generic = Generic::create([
                            'generic_name' => $genericName,
                            'slug' => Str::slug($genericName . '-' . uniqid()),
                            'is_active' => true
                        ]);
                        $genericId = $generic->generic_id;
                        $this->genericMap[$genericName] = $genericId;
                    }
                }

                if (empty($genericId) && isset($row['generic_id'])) {
                    $genericId = $row['generic_id'];
                }

                $data[] = [
                    'generic_id' => $genericId,
                    'indication_name' => $name,
                    'indication_code' => $row['indication_code'] ?? $row['indication code'] ?? null,
                    'description' => $row['description'] ?? null,
                    'severity' => $row['severity'] ?? 'Moderate',
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
                Log::error("Indication Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("Indication Import completed: {$this->successCount} success, {$this->errorCount} errors");
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('indications')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'indication_name' => 'required|string|max:255',
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