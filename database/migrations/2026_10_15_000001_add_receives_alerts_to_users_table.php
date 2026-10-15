<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Offline alerts went to every user assigned to a site. Observers are
     * typically students reading the data, who should not get maintenance
     * e-mails: the alerts become a per-user choice. Existing administrators
     * and agents keep receiving them.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('receives_alerts')->default(false)->after('status');
        });

        DB::table('users')->whereIn('role', ['admin', 'agent'])->update(['receives_alerts' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('receives_alerts');
        });
    }
};
