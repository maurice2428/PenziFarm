<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AnimalTagCorrectionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::findOrCreate(
            'correct animal identity',
            'web'
        );

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'Administrator',
                'Admin',
            ])
            ->get()
            ->each(
                fn (Role $role) => $role->givePermissionTo($permission)
            );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
