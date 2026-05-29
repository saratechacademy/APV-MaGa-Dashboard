<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weather_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->float('temp_c')->nullable();
            $table->float('humidity_pct')->nullable();
            $table->float('pressure_hpa')->nullable();
            $table->float('wind_speed_ms')->nullable();
            $table->string('wind_direction')->nullable();
            $table->float('irradiance_wm2')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};