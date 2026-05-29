<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agriculture_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('record_type', ['yield', 'measurement'])->default('measurement');
            $table->string('zone')->nullable();
            $table->string('crop_type')->nullable();
            $table->string('growth_stage')->nullable();
            $table->float('fresh_yield_kg')->nullable();
            $table->float('dry_yield_kg')->nullable();
            $table->float('plot_area_m2')->nullable();
            $table->float('plant_height_cm')->nullable();
            $table->float('leaf_area_index')->nullable();
            $table->float('canopy_cover_pct')->nullable();
            $table->float('chlorophyll_spad')->nullable();
            $table->enum('plant_health', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->string('recorded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agriculture_records');
    }
};