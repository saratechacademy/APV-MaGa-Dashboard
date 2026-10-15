<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Offline alerts per sensor, on top of the per-category ones (see
     * SiteOfflineMonitor). Sensors already silent when this runs are recorded
     * as such, so deploying it does not e-mail about outages that are weeks old.
     */
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->timestamp('offline_since')->nullable();
        });

        $multiplier = (int) config('alerts.sensor_threshold_multiplier', 3);

        $sensors = DB::table('site_parameters')
            ->join('site_categories', 'site_categories.id', '=', 'site_parameters.site_category_id')
            ->where('site_parameters.input_type', 'sensor')
            ->select('site_parameters.id', 'site_categories.offline_threshold_minutes')
            ->get();

        foreach ($sensors as $sensor) {
            $last = DB::table('sensor_readings')->where('site_parameter_id', $sensor->id)->max('read_at');
            $threshold = max(1, (int) ($sensor->offline_threshold_minutes ?: 5)) * $multiplier;

            if ($last && $last < now()->subMinutes($threshold)->toDateTimeString()) {
                DB::table('site_parameters')->where('id', $sensor->id)->update(['offline_since' => $last]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('offline_since');
        });
    }
};
