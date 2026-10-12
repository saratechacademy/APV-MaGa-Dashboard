<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Links a site to its HOBOnet weather/PAR station on LI-COR Cloud
        // (the gateway's serial, e.g. 22490036). Null = not fed by LI-COR.
        // Plus the outcome of the last sync, as for ThingsBoard.
        Schema::table('sites', function (Blueprint $table) {
            $table->string('licor_device_serial', 50)->nullable()->after('thingsboard_sync_error');
            $table->timestamp('licor_synced_at')->nullable()->after('licor_device_serial');
            $table->string('licor_sync_error', 500)->nullable()->after('licor_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['licor_device_serial', 'licor_synced_at', 'licor_sync_error']);
        });
    }
};
