<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PenziModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulePermissions = [
            // Animal Health Products
            'view health products', 'create health products', 'edit health products',
            'delete health products', 'restore health products', 'force delete health products',
            'export health products',

            // Gestation Rules
            'view gestation rules', 'create gestation rules', 'edit gestation rules',
            'delete gestation rules', 'restore gestation rules', 'force delete gestation rules',

            // Inventory Items
            'view inventory items', 'create inventory items', 'edit inventory items',
            'delete inventory items', 'restore inventory items', 'force delete inventory items',
            'export inventory items', 'activate inventory items', 'deactivate inventory items',

            // Stock Movements: system-generated and immutable
            'view stock movements', 'print stock movements', 'export stock movements',
            'manage stock movements',

            // Stock Adjustments
            'view stock adjustments', 'create stock adjustments', 'export stock adjustments',

            // Accounting Reconciliations
            'view accounting reconciliations', 'create accounting reconciliations',
            'edit accounting reconciliations', 'refresh accounting reconciliations',
            'approve accounting reconciliations', 'complete accounting reconciliations',
            'reopen accounting reconciliations', 'export accounting reconciliations',

            // Source Posting Audit
            'view accounting source postings', 'inspect accounting source postings',
            'export accounting source postings',

            // Posting Failures
            'view accounting posting failures', 'retry accounting posting failures',
            'ignore accounting posting failures', 'archive accounting posting failures',
            'restore accounting posting failures', 'export accounting posting failures',

            // Kenya Tax Dashboard and Rules
            'view kenya tax compliance',
            'view accounting tax settings', 'create accounting tax settings',
            'edit accounting tax settings', 'activate accounting tax settings',
            'deactivate accounting tax settings', 'export accounting tax settings',

            // Tax Register
            'view accounting tax transactions', 'edit accounting tax transactions',
            'mark accounting tax transactions filed', 'mark accounting tax transactions paid',
            'export accounting tax transactions',

            // Restricted accounting maintenance
            'run accounting auto posting', 'run accounting backfill',
            'manage accounting module',
        ];

        foreach (array_values(array_unique($modulePermissions)) as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        // Preserve users/roles that were assigned the old generic product aliases.
        $legacyAliases = [
            'view products' => 'view health products',
            'create products' => 'create health products',
            'edit products' => 'edit health products',
            'delete products' => 'delete health products',
            'restore products' => 'restore health products',
            'force delete products' => 'force delete health products',
            'export products' => 'export health products',
        ];

        foreach ($legacyAliases as $legacy => $canonical) {
            $legacyPermission = Permission::query()
                ->where('name', $legacy)
                ->where('guard_name', 'web')
                ->first();

            if (! $legacyPermission) {
                continue;
            }

            $canonicalPermission = Permission::findOrCreate($canonical, 'web');

            foreach ($legacyPermission->roles as $role) {
                $role->givePermissionTo($canonicalPermission);
            }

            $directUsers = \App\Models\User::query()
                ->whereHas(
                    'permissions',
                    fn ($query) =>
                        $query->whereKey($legacyPermission->getKey())
                )
                ->get();

            foreach ($directUsers as $user) {
                $user->givePermissionTo($canonicalPermission);
            }
        }

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->get();

        foreach (['Administrator', 'Admin'] as $roleName) {
            Role::findOrCreate($roleName, 'web')
                ->syncPermissions($allPermissions);
        }

        $accountingPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->where(function ($query): void {
                $query
                    ->where('name', 'like', '%accounting%')
                    ->orWhere('name', 'view kenya tax compliance')
                    ->orWhere('name', 'view cash flow statement')
                    ->orWhere('name', 'view trial balance')
                    ->orWhere('name', 'view general ledger')
                    ->orWhere('name', 'view profit and loss')
                    ->orWhere('name', 'view balance sheet');
            })
            ->get();

        foreach (['Finance', 'Accountant'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo($accountingPermissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
