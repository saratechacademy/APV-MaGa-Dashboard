<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('agent'); // agent ou observateur
            $table->timestamps();
            $table->unique(['site_id', 'user_id']);
        });

        // Migrer les données existantes — user_id actuel devient agent du site
        DB::table('sites')->whereNotNull('user_id')->get()->each(function($site) {
            DB::table('site_user')->insertOrIgnore([
                'site_id'    => $site->id,
                'user_id'    => $site->user_id,
                'role'       => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_user');
    }
};