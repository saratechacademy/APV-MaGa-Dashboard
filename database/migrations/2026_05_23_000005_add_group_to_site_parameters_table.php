<?php
// database/migrations/2026_05_23_000005_add_group_to_site_parameters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('input_type');
            // null = paramètre indépendant
            // "Yield Data" = groupé avec les autres du même nom
        });

        // Set default groups for agriculture
        \DB::table('site_parameters')
            ->whereIn('slug', ['crop_type','fresh_yield','dry_yield'])
            ->update(['group_name' => 'Yield Data']);

        \DB::table('site_parameters')
            ->whereIn('slug', ['plant_height','canopy_cover','plant_health','leaf_area_index'])
            ->update(['group_name' => 'Plant Measurements']);
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};