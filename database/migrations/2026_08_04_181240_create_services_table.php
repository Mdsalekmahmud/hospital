<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            
            $table->string('service_name', 500);  
            $table->string('price')->nullable();
            
            // Foreign key to service_categories
            $table->foreignId('service_category_id')
                  ->nullable()
                  ->constrained('service_categories')
                  ->onDelete('set null');
            
            $table->string('category_name_backup')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
    
            $table->index('service_name');
            $table->index('price');
            $table->index('service_category_id');
            
        
            $table->fullText('service_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};