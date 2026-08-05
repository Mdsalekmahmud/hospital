<?php

namespace App\Console\Commands;

use App\Imports\MedicineImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportMedicine extends Command
{
    protected $signature = 'import:medicine {--path=storage/app/public/csv/medicine.csv}';
    protected $description = 'Import medicine data from CSV file';

    public function handle()
    {
        $path = $this->option('path');
        
        if (!file_exists($path)) {
            $this->error("❌ File not found: {$path}");
            return 1;
        }

        $this->info("📂 Importing from: {$path}");
        $this->newLine();

        try {
            $import = new MedicineImport();
            Excel::import($import, $path);
            
            $this->info("✅ Import completed successfully!");
            $this->line("   Success: {$import->getSuccessCount()}");
            $this->line("   Errors: {$import->getErrorCount()}");
            
            if (!empty($import->getErrors())) {
                $this->newLine();
                $this->warn("⚠️ Error Details (first 20):");
                foreach (array_slice($import->getErrors(), 0, 20) as $error) {
                    $this->line("   - {$error}");
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}