<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le compte gérant par défaut
        User::create([
            'name' => 'Gérant',
            'email' => 'gerant@smartstock.com',
            'password' => Hash::make('password'), // Changez ce mot de passe en production !
            'role' => 'gerant',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Créer un compte vendeur de test
        User::create([
            'name' => 'Vendeur Test',
            'email' => 'vendeur@smartstock.com',
            'password' => Hash::make('password'),
            'role' => 'vendeur',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}