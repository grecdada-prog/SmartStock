<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'activate users',
            'deactivate users',
            
            // Product management
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Category management
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Stock management
            'view stock',
            'manage stock',
            'view stock movements',
            
            // Sales management
            'view sales',
            'create sales',
            'view own sales',
            'delete sales',
            
            // Reports and analytics
            'view reports',
            'view analytics',
            'export data',
            
            // Activity logs
            'view activity logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Super Admin - Accès total
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Manager - Gestion complète sauf super admin
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'activate users',
            'deactivate users',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            'view stock',
            'manage stock',
            'view stock movements',
            'view sales',
            'view reports',
            'view analytics',
            'export data',
            'view activity logs',
        ]);

        // Seller - Ventes uniquement
        $seller = Role::create(['name' => 'seller']);
        $seller->givePermissionTo([
            'view products',
            'view stock',
            'create sales',
            'view own sales',
        ]);

        // Créer un Super Admin par défaut
        $superAdminUser = User::create([
            'name' => 'Admin',
            'email' => 'nanguefyllias@gmail.com',
            'password' => Hash::make('Dorab237@'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $superAdminUser->assignRole('super_admin');

        $this->command->info('Super Admin créé avec succès!');
        $this->command->info('Email: nanguefyllias@gmail.com');
        $this->command->info('Password: Dorab237@');
    }
}