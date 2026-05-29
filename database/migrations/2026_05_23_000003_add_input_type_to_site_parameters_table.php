<?php
// database/migrations/2026_05_23_000003_add_input_type_to_site_parameters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->string('input_type')->default('sensor')->after('data_type');
            // sensor = collecté par ESP32/API
            // manual = saisi manuellement par l'agent
        });

        // Update defaults: agriculture params → manual
        \DB::table('site_parameters')
            ->whereIn('slug', ['crop_type','plant_height','fresh_yield','dry_yield',
                               'canopy_cover','plant_health','leaf_area_index',
                               'growth_stage','observations'])
            ->update(['input_type' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('input_type');
        });
    }
};