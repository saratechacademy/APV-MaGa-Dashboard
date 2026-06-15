<?php
// database/migrations/2026_06_14_000001_add_offline_threshold_to_site_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_categories', function (Blueprint $table) {
            // Délai (en minutes) sans donnée capteur avant qu'un paramètre
            // soit considéré "No data"/"Stale" sur le dashboard.
            $table->unsignedInteger('offline_threshold_minutes')->default(5)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('site_categories', function (Blueprint $table) {
            $table->dropColumn('offline_threshold_minutes');
        });
    }
};