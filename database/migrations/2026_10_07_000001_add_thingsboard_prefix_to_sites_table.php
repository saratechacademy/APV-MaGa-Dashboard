<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Links a site to its devices on the partner's ThingsBoard instance,
        // which are all named "<prefix>-<zone>-<sensor>-<n>" (UTG-APV-Valve-1,
        // AfriFarm-General-Pressure-0...). Null = site is not fed by ThingsBoard.
        Schema::table('sites', function (Blueprint $table) {
            $table->string('thingsboard_prefix', 50)->nullable()->after('api_key');
            // Outcome of the last sync, so a stalled or failing sync is visible
            // in the admin instead of only as charts that quietly stop moving.
            $table->timestamp('thingsboard_synced_at')->nullable()->after('thingsboard_prefix');
            $table->string('thingsboard_sync_error', 500)->nullable()->after('thingsboard_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['thingsboard_prefix', 'thingsboard_synced_at', 'thingsboard_sync_error']);
        });
    }
};
