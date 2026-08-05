<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DosageFormImport;
use App\Imports\DrugClassImport;
use App\Imports\GenericImport;
use App\Imports\BrandImport;
use App\Imports\ManufacturerImport;
use App\Imports\IndicationImport;
use App\Imports\ServiceImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportAllCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:all-csv 
                            {--path=storage/app/public/csv/ : CSV files directory path}
                            {--only=* : Import only specific files (dosage_form,drug_class,etc)}
                            {--skip=* : Skip specific imports}
                            {--force : Force import even if errors occur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all 7 CSV files into the database using ToCollection';

    /**
     * List of imports
     */
    private array $imports = [
        'dosage_form' => [
            'class' => DosageFormImport::class,
            'file' => 'dosage_form.csv',
            'description' => 'Dosage Forms'
        ],
        'drug_class' => [
            'class' => DrugClassImport::class,
            'file' => 'drug_class.csv',
            'description' => 'Drug Classes'
        ],
        'manufacturer' => [
            'class' => ManufacturerImport::class,
            'file' => 'manufacturer.csv',
            'description' => 'Manufacturers'
        ],
        'generic' => [
            'class' => GenericImport::class,
            'file' => 'generic.csv',
            'description' => 'Generics'
        ],
        'brand' => [
            'class' => BrandImport::class,
            'file' => 'brand.csv',
            'description' => 'Brands'
        ],
        'indication' => [
            'class' => IndicationImport::class,
            'file' => 'indication.csv',
            'description' => 'Indications'
        ],
        'service' => [
            'class' => ServiceImport::class,
            'file' => 'services.csv',
            'description' => 'Services'
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting bulk import using ToCollection...');
        $this->newLine();

        $basePath = $this->option('path');
        $only = $this->option('only');
        $skip = $this->option('skip');
        $force = $this->option('force');

        $imports = $this->filterImports($only, $skip);

        if (empty($imports)) {
            $this->error('❌ No imports to process!');
            return 1;
        }

        DB::beginTransaction();

        try {
            $results = [];
            $totalSuccess = 0;
            $totalErrors = 0;

            foreach ($imports as $key => $config) {
                $filePath = rtrim($basePath, '/') . '/' . $config['file'];
                
                if (!file_exists($filePath)) {
                    $this->warn("⚠️  File not found: {$filePath}");
                    continue;
                }

                $this->info("📂 Importing {$config['description']} from: {$config['file']}");
                $this->line("   File size: " . $this->formatFileSize(filesize($filePath)));
                
                try {
                    $import = new $config['class']();
                    Excel::import($import, $filePath);
                    
                    $success = $import->getSuccessCount();
                    $errors = $import->getErrorCount();
                    $errorList = $import->getErrors();
                    
                    $results[$key] = [
                        'status' => '✅ Success',
                        'success' => $success,
                        'errors' => $errors
                    ];
                    
                    $totalSuccess += $success;
                    $totalErrors += $errors;
                    
                    $this->line("   ✅ {$success} records imported successfully");
                    
                    if ($errors > 0) {
                        $this->warn("   ⚠️  {$errors} errors occurred");
                        if (!$force) {
                            $this->line("   📝 First 5 errors:");
                            foreach (array_slice($errorList, 0, 5) as $error) {
                                $this->line("      - {$error}");
                            }
                            if (count($errorList) > 5) {
                                $this->line("      ... and " . (count($errorList) - 5) . " more errors");
                            }
                        }
                    }
                    
                } catch (Throwable $e) {
                    $results[$key] = [
                        'status' => '❌ Failed',
                        'error' => $e->getMessage()
                    ];
                    $this->error("   ❌ Error: " . $e->getMessage());
                    
                    if (!$force) {
                        throw $e;
                    }
                }
                
                $this->newLine();
            }

            // Update counts
            if ($totalSuccess > 0) {
                $this->updateCounts();
            }

            if ($force) {
                DB::commit();
                $this->info('✅ Import completed with some errors (force mode)');
            } else {
                DB::commit();
                $this->info('✅ All imports completed successfully!');
            }

            // Show summary
            $this->newLine();
            $this->showSummary($results, $totalSuccess, $totalErrors);

            return 0;

        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('❌ Import failed: ' . $e->getMessage());
            $this->error('   Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Filter imports based on only/skip options
     */
    private function filterImports(array $only, array $skip): array
    {
        if (!empty($only)) {
            return array_filter($this->imports, function ($key) use ($only) {
                return in_array($key, $only);
            }, ARRAY_FILTER_USE_KEY);
        }

        if (!empty($skip)) {
            return array_filter($this->imports, function ($key) use ($skip) {
                return !in_array($key, $skip);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $this->imports;
    }

    /**
     * Update all count fields
     */
    private function updateCounts(): void
    {
        $this->info('🔄 Updating counts...');
        
        try {
            // Update dosage form brand counts
            DB::statement('
                UPDATE dosage_forms df
                SET brand_count = (
                    SELECT COUNT(*) FROM brands b WHERE b.dosage_form_id = df.dosage_form_id
                )
            ');

            // Update drug class generic counts
            DB::statement('
                UPDATE drug_classes dc
                SET generic_count = (
                    SELECT COUNT(*) FROM generics g WHERE g.drug_class_id = dc.drug_class_id
                )
            ');

            // Update manufacturer brand counts
            DB::statement('
                UPDATE manufacturers m
                SET brand_count = (
                    SELECT COUNT(*) FROM brands b WHERE b.manufacturer_id = m.manufacturer_id
                )
            ');

            // Update service category counts
            DB::statement('
                UPDATE service_categories sc
                SET service_count = (
                    SELECT COUNT(*) FROM services s WHERE s.service_category_id = sc.id
                )
            ');
            
            $this->info('✅ Counts updated successfully!');
            
        } catch (\Exception $e) {
            $this->warn('⚠️  Failed to update counts: ' . $e->getMessage());
        }
    }

    /**
     * Show import summary
     */
    private function showSummary(array $results, int $totalSuccess, int $totalErrors): void
    {
        $this->info('📊 Import Summary:');
        $this->line("   Total Success: {$totalSuccess}");
        $this->line("   Total Errors: {$totalErrors}");
        $this->newLine();

        $this->info('📋 Detailed Results:');
        foreach ($results as $key => $result) {
            if ($result['status'] === '✅ Success') {
                $this->line("   {$key}: {$result['status']} - {$result['success']} imported, {$result['errors']} errors");
            } else {
                $this->line("   {$key}: {$result['status']} - {$result['error']}");
            }
        }

        $this->newLine();
        $this->showStats();
    }

    /**
     * Show database statistics
     */
    private function showStats(): void
    {
        $this->info('📊 Database Statistics:');
        
        $stats = [
            'Dosage Forms' => \App\Models\DosageForm::count(),
            'Drug Classes' => \App\Models\DrugClass::count(),
            'Manufacturers' => \App\Models\Manufacturer::count(),
            'Generics' => \App\Models\Generic::count(),
            'Brands' => \App\Models\Brand::count(),
            'Indications' => \App\Models\Indication::count(),
            'Services' => \App\Models\Service::count(),
            'Service Categories' => \App\Models\ServiceCategory::count(),
        ];

        $table = [];
        foreach ($stats as $name => $count) {
            $table[] = ['Table' => $name, 'Total Records' => $count];
        }

        $this->table(['Table', 'Total Records'], $table);
    }

    /**
     * Format file size
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}