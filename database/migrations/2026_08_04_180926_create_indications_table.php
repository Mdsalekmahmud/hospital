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
        Schema::create('indications', function (Blueprint $table) {
             $table->id('indication_id');
            
            $table->foreignId('generic_id')
                  ->nullable()
                  ->constrained('generics', 'generic_id')
                  ->onDelete('cascade');
            
            $table->string('indication_name', 255);
            $table->string('indication_code')->nullable();
            $table->text('description')->nullable();
            $table->enum('severity', ['Mild', 'Moderate', 'Severe'])->default('Moderate');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('indication_name');
            $table->index('generic_id');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indications');
    }
};
