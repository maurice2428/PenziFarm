<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProcurementSafetyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'reverse purchase order payments',
            'reverse goods received notes',
            'cancel purchase orders',
            'archive suppliers',
            'deactivate inventory items',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'web'
            );
        }

        foreach (
            ['Administrator', 'Admin']
            as $roleName
        ) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo(
                    $permissions
                );
            }
        }
    }
}
