<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BreedingRiskPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'view breeding risk dashboard',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        foreach ([
            'Administrator',
            'Admin',
            'Manager',
            'Veterinary Officer',
        ] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo($permissions);
        }
    }
}
