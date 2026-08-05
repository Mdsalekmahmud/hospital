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
        Schema::create('dosage_forms', function (Blueprint $table) {
            $table->id('dosage_form_id');
            $table->string('dosage_form_name', 255);
            $table->string('slug', 255)->unique();
            $table->integer('brand_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('dosage_form_name');
            $table->index('slug');
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosage_forms');
    }
};
