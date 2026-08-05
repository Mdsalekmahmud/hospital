<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('brand_id')->nullable();
            $table->string('brand_name');
            $table->string('type')->nullable(); // allopathic, herbal
            $table->string('slug')->unique();
            $table->string('dosage_form')->nullable();
            $table->string('generic')->nullable();
            $table->string('strength')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('package_container')->nullable();
            $table->text('package_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('brand_name');
            $table->index('generic');
            $table->index('manufacturer');
            $table->index('type');
            $table->index('dosage_form');
        });
    }

    public function down()
    {
        Schema::dropIfExists('medicines');
    }
};