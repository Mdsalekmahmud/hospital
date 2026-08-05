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
        Schema::create('drug_classes', function (Blueprint $table) {
            $table->id('drug_class_id');
            $table->string('drug_class_name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->integer('generic_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('drug_class_name');
            $table->index('slug');
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drug_classes');
    }
};
