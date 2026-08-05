<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id('brand_id');
            $table->string('brand_name', 255);
            $table->string('slug', 255)->unique();
            
            // Foreign keys
            $table->foreignId('generic_id')
                  ->nullable()
                  ->constrained('generics', 'generic_id')
                  ->onDelete('set null');
            
            $table->foreignId('dosage_form_id')
                  ->nullable()
                  ->constrained('dosage_forms', 'dosage_form_id')
                  ->onDelete('set null');
            
            $table->foreignId('manufacturer_id')
                  ->nullable()
                  ->constrained('manufacturers', 'manufacturer_id')
                  ->onDelete('set null');
            
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('manufacturer_name_backup')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('brand_name');
            $table->index('slug');
            $table->index('generic_id');
            $table->index('dosage_form_id');
            $table->index('manufacturer_id');
            $table->index('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
