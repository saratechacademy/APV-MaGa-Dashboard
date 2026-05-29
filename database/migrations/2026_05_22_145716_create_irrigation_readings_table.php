<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('irrigation_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->float('flow_rate_lmin')->nullable();
            $table->float('moisture_zone_a_pct')->nullable();
            $table->float('moisture_zone_b_pct')->nullable();
            $table->float('moisture_zone_c_pct')->nullable();
            $table->boolean('valve_v01_open')->default(false);
            $table->boolean('valve_v02_open')->default(false);
            $table->boolean('valve_v03_open')->default(false);
            $table->float('pump_runtime_min')->nullable();
            $table->float('water_collected_l')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('irrigation_readings');
    }
};