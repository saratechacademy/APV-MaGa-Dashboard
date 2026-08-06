<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // warning/critical_threshold used a single hardcoded "alert when value
        // <= threshold" comparison, correct for "low is bad" metrics (tank
        // level, borehole level) but backwards for "high is bad" ones (panel
        // temperature) — a 50°C reading was flagged "Warning" while a genuine
        // 85°C overheat with threshold=70 was not. Direction is now explicit
        // per parameter.
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->enum('threshold_direction', ['below', 'above'])
                  ->default('below')
                  ->after('critical_threshold');
        });

        // Known "high is bad" default parameter; everything else keeps the
        // existing "below" behavior it already had.
        DB::table('site_parameters')
            ->where('slug', 'panel_temperature')
            ->update(['threshold_direction' => 'above']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('threshold_direction');
        });
    }
};
