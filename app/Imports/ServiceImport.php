<?php

namespace App\Imports;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    private array $errors = [];
    private int $successCount = 0;
    private int $errorCount = 0;
    private array $categoryCache = [];

    public function __construct()
    {
        $this->categoryCache = ServiceCategory::pluck('id', 'name')->toArray();
    }

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
                // Check if row is empty
                $serviceName = trim($row['service_name'] ?? '');
                $price = $row['price'] ?? null;
                
                if (empty($serviceName) && empty($price)) {
                    $this->errors[] = "Row {$rowNumber}: Empty row skipped";
                    continue;
                }

                if (empty($serviceName)) {
                    $this->errors[] = "Row {$rowNumber}: Service name is empty";
                    $this->errorCount++;
                    continue;
                }

                // Get or create category
                $categoryName = trim($row['service_category'] ?? 'Uncategorized');
                $categoryId = $this->getOrCreateCategory($categoryName);

                // Clean price - remove BDT and any extra spaces
                $cleanPrice = isset($price) ? trim(str_replace('BDT', '', $price)) : null;
                $cleanPrice = $cleanPrice ? preg_replace('/[^0-9.]/', '', $cleanPrice) : null;

                // Generate slug
                $slug = Str::slug($serviceName . '-' . uniqid());

                $data[] = [
                    'service_name' => $serviceName,
                    'price' => $cleanPrice,
                    'service_category_id' => $categoryId,
                    'category_name_backup' => $categoryName,
                    'slug' => $slug,
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
                Log::error("Service Import Error (Row {$rowNumber}): " . $e->getMessage());
            }
        }

        // Insert remaining data
        if (!empty($data)) {
            $this->bulkInsert($data);
        }

        Log::info("Service Import completed: {$this->successCount} success, {$this->errorCount} errors");
        
        if (!empty($this->errors)) {
            Log::warning("Service Import Errors: " . implode("\n", array_slice($this->errors, 0, 10)));
        }
    }

    private function getOrCreateCategory(string $name): int
    {
        if (isset($this->categoryCache[$name])) {
            return $this->categoryCache[$name];
        }

        try {
            $category = ServiceCategory::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name . '-' . uniqid()),
                    'description' => "Services under {$name} category",
                    'is_active' => true
                ]
            );

            $this->categoryCache[$name] = $category->id;
            return $category->id;

        } catch (\Exception $e) {
            Log::error("Failed to create category: {$name} - " . $e->getMessage());
            // Create with fallback
            $category = ServiceCategory::create([
                'name' => $name . '-' . uniqid(),
                'slug' => Str::slug($name . '-' . uniqid()),
                'description' => "Services under {$name} category",
                'is_active' => true
            ]);
            $this->categoryCache[$name] = $category->id;
            return $category->id;
        }
    }

    private function bulkInsert(array $data): void
    {
        try {
            DB::table('services')->insert($data);
            $this->successCount += count($data);
        } catch (\Exception $e) {
            Log::error('Bulk insert failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'service_name' => 'nullable|string|max:500',
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