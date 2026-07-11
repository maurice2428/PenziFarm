<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountingPdfPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'download accounting pdf reports',
            'view cash flow statement',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ([
            'Administrator',
            'Admin',
            'Director',
            'Manager',
            'Finance',
            'Accountant',
        ] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }
    }
}
