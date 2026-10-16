<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0001_01_01_000001 already creates this exact table. Never drop it:
        // on an existing database that would delete every site.
        if (Schema::hasTable('sites')) {
            return;
        }

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country');
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->float('capacity_kw')->nullable();
            $table->float('area_m2')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};