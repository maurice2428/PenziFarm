<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LocationsModuleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'view locations',
            'create locations',
            'edit locations',
            'delete locations',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        /*
         * Adds starter permissions only. Existing permissions on roles are never wiped.
         */
        $rolePermissions = [
            'Administrator' => $permissionNames,
            'Director' => ['view locations'],
            'Manager' => ['view locations', 'create locations', 'edit locations'],
            'Veterinary Officer' => ['view locations', 'create locations', 'edit locations'],
            'Farm Supervisor' => ['view locations', 'create locations', 'edit locations'],
            'Data Entry Clerk' => ['view locations', 'create locations'],
        ];

        foreach ($rolePermissions as $roleName => $names) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo($names);
            }
        }

        /*
         * Required default station. Exact map coordinates should be set from
         * the Locations screen after selecting the real farm point on the map.
         */
        Location::query()->update(['is_default' => false]);

        Location::updateOrCreate(
            ['name' => 'Muserechi'],
            [
                'code' => 'MUSERECHI',
                'type' => 'station',
                'address' => 'Muserechi',
                'county' => null,
                'sub_county' => null,
                'ward' => null,
                'latitude' => null,
                'longitude' => null,
                'place_label' => null,
                'is_active' => true,
                'is_default' => true,
                'notes' => 'Default livestock station. Open this record and use the map selector to capture the exact farm coordinates and auto-fill location details.',
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
