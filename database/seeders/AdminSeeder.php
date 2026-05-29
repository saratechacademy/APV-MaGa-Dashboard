<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'         => 'Administrateur APV-MaGa',
            'email'        => 'admin@apvmaga.com',
            'password'     => Hash::make('apvmaga@2026'),
            'role'         => 'admin',
            'status'       => 'active',
            'organisation' => 'APV-MaGa Project',
            'country'      => 'GMB',
        ]);
    }
}