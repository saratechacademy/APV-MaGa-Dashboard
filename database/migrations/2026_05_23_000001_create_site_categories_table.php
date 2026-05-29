<?php
// database/migrations/2026_05_23_000001_create_site_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // Solar, Water, Irrigation...
            $table->string('slug');           // solar, water, irrigation...
            $table->string('icon')->nullable(); // ☀ 💧 🔋 🌡 🌿
            $table->string('color')->nullable(); // #15803d, #1d6ed8...
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');           // Solar output, Solar irradiance...
            $table->string('slug');           // solar_output, solar_irradiance...
            $table->string('unit')->nullable(); // kW, W/m², °C, %, m, L/min...
            $table->string('data_type')->default('float'); // float, integer, boolean, string
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->decimal('warning_threshold', 10, 4)->nullable();
            $table->decimal('critical_threshold', 10, 4)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_dashboard')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_parameters');
        Schema::dropIfExists('site_categories');
    }
};