<?php

use App\Models\SiteCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Set when an offline alert has been sent for the category (to the
        // time its data stopped), cleared when its data comes back. It is what
        // makes an alert go out once per outage instead of at every check.
        Schema::table('site_categories', function (Blueprint $table) {
            $table->timestamp('offline_since')->nullable()->after('offline_threshold_minutes');
        });

        // Categories already silent today are recorded as such without an
        // e-mail: turning alerts on must not announce month-old outages. They
        // will still produce the "reporting again" e-mail when they come back.
        $lastReadings = DB::table('sensor_readings')
            ->join('site_parameters', 'site_parameters.id', '=', 'sensor_readings.site_parameter_id')
            ->where('site_parameters.input_type', 'sensor')
            ->groupBy('site_parameters.site_category_id')
            ->select('site_parameters.site_category_id', DB::raw('MAX(sensor_readings.read_at) as last_at'))
            ->pluck('last_at', 'site_category_id');

        foreach (DB::table('site_categories')->get(['id', 'offline_threshold_minutes']) as $category) {
            $last      = $lastReadings[$category->id] ?? null;
            $threshold = $category->offline_threshold_minutes ?? SiteCategory::DEFAULT_OFFLINE_THRESHOLD_MINUTES;

            if ($last && strtotime($last) < now()->subMinutes($threshold)->getTimestamp()) {
                DB::table('site_categories')->where('id', $category->id)->update(['offline_since' => $last]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('site_categories', function (Blueprint $table) {
            $table->dropColumn('offline_since');
        });
    }
};
