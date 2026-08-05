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
        Schema::create('generics', function (Blueprint $table) {
             $table->id('generic_id');
            $table->string('generic_name', 255);
            $table->string('slug', 255)->unique();
            
            // Foreign key to drug_classes
            $table->foreignId('drug_class_id')
                  ->nullable()
                  ->constrained('drug_classes', 'drug_class_id')
                  ->onDelete('set null');
            
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();
            $table->text('indication')->nullable();
            $table->text('contraindication')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('pharmacology')->nullable();
            $table->text('dosage')->nullable();
            $table->text('interaction')->nullable();
            $table->text('precautions')->nullable();
            $table->text('pregnancy_lactation')->nullable();
            $table->text('pediatric_usage')->nullable();
            $table->text('overdose_effects')->nullable();
            $table->text('storage_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('generic_name');
            $table->index('slug');
            $table->index('drug_class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generics');
    }
};
