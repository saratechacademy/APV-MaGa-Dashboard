<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One reading per parameter and instant, enforced by the database.
        // Until now the absence of duplicates relied on the syncs resuming
        // from the right place and on a cache lock that `artisan cache:clear`
        // (part of the deployment steps) releases mid-run.
        //
        // Any duplicate that slipped in is removed first, keeping the oldest row.
        $duplicates = DB::table('sensor_readings')
            ->select('site_parameter_id', 'read_at', DB::raw('MIN(id) as keep_id'))
            ->groupBy('site_parameter_id', 'read_at')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('sensor_readings')
                ->where('site_parameter_id', $duplicate->site_parameter_id)
                ->where('read_at', $duplicate->read_at)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        // The unique index is created before the plain one is dropped, so the
        // foreign key on site_parameter_id always has an index to rely on (MariaDB).
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->unique(['site_parameter_id', 'read_at']);
        });
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropIndex(['site_parameter_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->index(['site_parameter_id', 'read_at']);
        });
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropUnique(['site_parameter_id', 'read_at']);
        });
    }
};
