<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProgenyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view progeny analytics',
            'print progeny reports',
            'view breeding outcomes',
            'edit breeding outcomes',
            'manage breeding reviews',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach (['Administrator', 'Admin'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }

        foreach (['Manager', 'Veterinary Officer'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo([
                'view progeny analytics',
                'print progeny reports',
                'view breeding outcomes',
                'edit breeding outcomes',
                'manage breeding reviews',
            ]);
        }
    }
}
