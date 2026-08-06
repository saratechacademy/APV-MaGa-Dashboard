<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // reading_date was a DATE column, so the exact submission time that
        // ManualReadingController::store() computes (setTimeFrom(now())) was
        // silently truncated to midnight on save. That made same-day entries
        // for the same parameter indistinguishable — "latest" ordering and
        // export grouping (both key off the full timestamp) collide whenever
        // an agent submits more than one reading per day, which does happen
        // in production data.
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->dateTime('reading_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_readings', function (Blueprint $table) {
            $table->date('reading_date')->change();
        });
    }
};
