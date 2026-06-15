<?php
// database/migrations/2026_06_13_000002_create_actuator_commands_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('actuator_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_parameter_id')->constrained()->onDelete('cascade');

            // 0 = OFF, 1 = ON (commande souhaitée)
            $table->unsignedTinyInteger('desired_state')->default(0);

            // Etat réel rapporté par l'ESP32 via /api/sensors (rempli par ApiController::store)
            $table->unsignedTinyInteger('reported_state')->nullable();
            $table->timestamp('reported_at')->nullable();

            // Qui a modifié la commande en dernier
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Une seule commande par paramètre
            $table->unique('site_parameter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actuator_commands');
    }
};