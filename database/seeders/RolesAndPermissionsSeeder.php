<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view own profile',
            'edit own profile',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $finance = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $vet = Role::firstOrCreate(['name' => 'Vet', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);

        $finance->syncPermissions([
            'view dashboard',
            'view own profile',
            'edit own profile',
        ]);

        $manager->syncPermissions([
            'view dashboard',
            'view own profile',
            'edit own profile',
        ]);

        $vet->syncPermissions([
            'view dashboard',
            'view own profile',
            'edit own profile',
        ]);

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'phone' => '0700000000',
            ]
        );

        if (! $user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }
    }
}
