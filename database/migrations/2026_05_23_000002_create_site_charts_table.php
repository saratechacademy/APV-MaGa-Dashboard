<?php
// database/migrations/2026_05_23_000002_create_site_charts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('chart_type')->default('line'); // line, bar, area
            $table->integer('sort_order')->default(0);
            $table->integer('height')->default(220); // px
            $table->string('col_span')->default('full'); // full, half, third
            $table->boolean('show_legend')->default(true);
            $table->boolean('dual_axis')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_chart_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_chart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_parameter_id')->constrained()->cascadeOnDelete();
            $table->string('color')->default('#1d6ed8');
            $table->string('axis')->default('left'); // left, right
            $table->boolean('dashed')->default(false);
            $table->boolean('fill')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_chart_parameters');
        Schema::dropIfExists('site_charts');
    }
};