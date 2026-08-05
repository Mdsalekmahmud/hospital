// routes/console.php
<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DosageFormImport;
use App\Imports\DrugClassImport;
use App\Imports\GenericImport;
use App\Imports\BrandImport;
use App\Imports\ManufacturerImport;
use App\Imports\IndicationImport;
use App\Imports\ServiceImport;

// ============================================
// COMMAND: Import all CSV files
// ============================================
Artisan::command('import:all-csv', function () {
    $this->components->info('🚀 Starting bulk import using ToCollection...');
    $this->newLine();

    $basePath = storage_path('app/public/csv/');
    
    $imports = [
        'dosage_form' => [
            'class' => DosageFormImport::class,
            'file' => 'dosage_form.csv',
            'desc' => 'Dosage Forms'
        ],
        'drug_class' => [
            'class' => DrugClassImport::class,
            'file' => 'drug_class.csv',
            'desc' => 'Drug Classes'
        ],
        'manufacturer' => [
            'class' => ManufacturerImport::class,
            'file' => 'manufacturer.csv',
            'desc' => 'Manufacturers'
        ],
        'generic' => [
            'class' => GenericImport::class,
            'file' => 'generic.csv',
            'desc' => 'Generics'
        ],
        'brand' => [
            'class' => BrandImport::class,
            'file' => 'brand.csv',
            'desc' => 'Brands'
        ],
        'indication' => [
            'class' => IndicationImport::class,
            'file' => 'indication.csv',
            'desc' => 'Indications'
        ],
        'service' => [
            'class' => ServiceImport::class,
            'file' => 'services.csv',
            'desc' => 'Services'
        ],
    ];

    DB::beginTransaction();

    try {
        $totalSuccess = 0;
        $totalErrors = 0;

        foreach ($imports as $key => $config) {
            $filePath = $basePath . $config['file'];
            
            if (!file_exists($filePath)) {
                $this->components->warn("File not found: {$filePath}");
                continue;
            }

            $this->components->task("Importing {$config['desc']}", function () use ($config, $filePath, &$totalSuccess, &$totalErrors) {
                try {
                    $import = new $config['class']();
                    Excel::import($import, $filePath);
                    
                    $totalSuccess += $import->getSuccessCount();
                    $totalErrors += $import->getErrorCount();
                    
                    return true;
                } catch (\Exception $e) {
                    throw $e;
                }
            });
        }

        // ============================================
        // UPDATE COUNTS - সরাসরি এখানে কোড লিখুন
        // ============================================
        $this->components->task('Updating counts', function () {
            try {
                // Check if tables exist
                $tables = DB::select('SHOW TABLES');
                $tableNames = array_map('current', $tables);
                
                // Update dosage form brand counts
                if (in_array('dosage_forms', $tableNames)) {
                    DB::statement('
                        UPDATE dosage_forms df 
                        SET brand_count = (
                            SELECT COUNT(*) FROM brands b 
                            WHERE b.dosage_form_id = df.dosage_form_id
                        )
                    ');
                }
                
                // Update drug class generic counts
                if (in_array('drug_classes', $tableNames)) {
                    DB::statement('
                        UPDATE drug_classes dc 
                        SET generic_count = (
                            SELECT COUNT(*) FROM generics g 
                            WHERE g.drug_class_id = dc.drug_class_id
                        )
                    ');
                }
                
                // Update manufacturer brand counts
                if (in_array('manufacturers', $tableNames)) {
                    DB::statement('
                        UPDATE manufacturers m 
                        SET brand_count = (
                            SELECT COUNT(*) FROM brands b 
                            WHERE b.manufacturer_id = m.manufacturer_id
                        )
                    ');
                }
                
                // Update service category counts
                if (in_array('service_categories', $tableNames)) {
                    DB::statement('
                        UPDATE service_categories sc 
                        SET service_count = (
                            SELECT COUNT(*) FROM services s 
                            WHERE s.service_category_id = sc.id
                        )
                    ');
                }
                
                return true;
            } catch (\Exception $e) {
                $this->warn('⚠️  Count update warning: ' . $e->getMessage());
                return true;
            }
        });

        DB::commit();
        
        $this->newLine();
        $this->components->info('✅ All imports completed successfully!');
        $this->components->info("📊 Total Success: {$totalSuccess}, Total Errors: {$totalErrors}");
        
        // Show stats
        $this->showStats();
        
    } catch (\Exception $e) {
        DB::rollBack();
        $this->components->error('❌ Import failed: ' . $e->getMessage());
    }
})->purpose('Import all CSV files into the database');

// ============================================
// showStats() ফাংশন (updateCounts এর বাইরে)
// ============================================
function showStats()
{
    $this->newLine();
    $this->components->info('📊 Database Statistics:');
    
    try {
        $stats = [];
        
        if (class_exists(\App\Models\DosageForm::class)) {
            $stats['Dosage Forms'] = \App\Models\DosageForm::count();
        }
        if (class_exists(\App\Models\DrugClass::class)) {
            $stats['Drug Classes'] = \App\Models\DrugClass::count();
        }
        if (class_exists(\App\Models\Manufacturer::class)) {
            $stats['Manufacturers'] = \App\Models\Manufacturer::count();
        }
        if (class_exists(\App\Models\Generic::class)) {
            $stats['Generics'] = \App\Models\Generic::count();
        }
        if (class_exists(\App\Models\Brand::class)) {
            $stats['Brands'] = \App\Models\Brand::count();
        }
        if (class_exists(\App\Models\Indication::class)) {
            $stats['Indications'] = \App\Models\Indication::count();
        }
        if (class_exists(\App\Models\Service::class)) {
            $stats['Services'] = \App\Models\Service::count();
        }
        if (class_exists(\App\Models\ServiceCategory::class)) {
            $stats['Service Categories'] = \App\Models\ServiceCategory::count();
        }
        
        foreach ($stats as $name => $count) {
            $this->line("   {$name}: {$count}");
        }
    } catch (\Exception $e) {
        $this->warn('Could not load statistics: ' . $e->getMessage());
    }
}

// ============================================
// INDIVIDUAL IMPORT COMMANDS
// ============================================

Artisan::command('import:dosage-form', function () {
    $this->info('📂 Importing Dosage Forms...');
    
    $filePath = storage_path('app/public/csv/dosage_form.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new DosageFormImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Dosage Forms imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import dosage forms from CSV');

Artisan::command('import:drug-class', function () {
    $this->info('📂 Importing Drug Classes...');
    
    $filePath = storage_path('app/public/csv/drug_class.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new DrugClassImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Drug Classes imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import drug classes from CSV');

Artisan::command('import:manufacturer', function () {
    $this->info('📂 Importing Manufacturers...');
    
    $filePath = storage_path('app/public/csv/manufacturer.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new ManufacturerImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Manufacturers imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import manufacturers from CSV');

Artisan::command('import:generic', function () {
    $this->info('📂 Importing Generics...');
    
    $filePath = storage_path('app/public/csv/generic.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new GenericImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Generics imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import generics from CSV');

Artisan::command('import:brand', function () {
    $this->info('📂 Importing Brands...');
    
    $filePath = storage_path('app/public/csv/brand.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new BrandImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Brands imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import brands from CSV');

Artisan::command('import:indication', function () {
    $this->info('📂 Importing Indications...');
    
    $filePath = storage_path('app/public/csv/indication.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new IndicationImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Indications imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import indications from CSV');

Artisan::command('import:service', function () {
    $this->info('📂 Importing Services...');
    
    $filePath = storage_path('app/public/csv/services.csv');
    
    if (!file_exists($filePath)) {
        $this->error("❌ File not found: {$filePath}");
        return;
    }

    try {
        $import = new ServiceImport();
        Excel::import($import, $filePath);
        
        $this->info('✅ Services imported successfully!');
        $this->line("   Success: {$import->getSuccessCount()}");
        $this->line("   Errors: {$import->getErrorCount()}");
        
    } catch (\Exception $e) {
        $this->error("❌ Error: " . $e->getMessage());
    }
})->purpose('Import services from CSV');

// List all commands
Artisan::command('import:list', function () {
    $this->info('📋 Available Import Commands:');
    $this->newLine();
    
    $commands = [
        'import:all-csv' => 'Import all CSV files',
        'import:dosage-form' => 'Import dosage forms',
        'import:drug-class' => 'Import drug classes',
        'import:manufacturer' => 'Import manufacturers',
        'import:generic' => 'Import generics',
        'import:brand' => 'Import brands',
        'import:indication' => 'Import indications',
        'import:service' => 'Import services',
    ];
    
    foreach ($commands as $command => $description) {
        $this->line("   php artisan {$command}");
        $this->line("      {$description}");
        $this->newLine();
    }
})->purpose('List all import commands');