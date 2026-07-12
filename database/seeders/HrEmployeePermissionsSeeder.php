<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrEmployeePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view employee payroll',
            'import employees',
            'promote employees',
            'demote employees',
            'suspend employees',
            'reinstate employees',
            'terminate employees',
            'manage disciplinary cases',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (['Administrator', 'Admin'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }
    }
}
