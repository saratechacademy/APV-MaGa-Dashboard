<?php
// database/migrations/2026_06_13_000001_add_control_type_to_site_parameters_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            // readonly    = affichage uniquement (état rapporté par le capteur)
            // controllable = un toggle ON/OFF est affiché et écrit dans actuator_commands
            $table->string('control_type')->default('readonly')->after('input_type');
        });
    }

    public function down(): void
    {
        Schema::table('site_parameters', function (Blueprint $table) {
            $table->dropColumn('control_type');
        });
    }
};