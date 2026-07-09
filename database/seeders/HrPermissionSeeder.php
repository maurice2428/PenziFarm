<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view employees', 'create employees', 'edit employees', 'delete employees', 'exit employees',
            'view departments', 'manage departments',
            'view attendance', 'manage attendance', 'adjust attendance',
            'view leave', 'create leave', 'approve leave', 'reject leave',
            'view salary advances', 'approve salary advances',
            'view payroll', 'generate payroll', 'review payroll', 'approve payroll', 'post payroll',
            'view payslips', 'generate payslips', 'email payslips',
            'view p9 forms', 'generate p9 forms',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $hrAdmin = Role::findOrCreate('HR Admin', 'web');
        $hrOfficer = Role::findOrCreate('HR Officer', 'web');
        $financeOfficer = Role::findOrCreate('Finance Officer', 'web');
        $manager = Role::findOrCreate('Department Manager', 'web');

        $superAdmin->syncPermissions(Permission::all());
        $hrAdmin->syncPermissions($permissions);
        $hrOfficer->syncPermissions([
            'view employees', 'create employees', 'edit employees',
            'view departments', 'manage departments',
            'view attendance', 'manage attendance', 'adjust attendance',
            'view leave', 'create leave', 'approve leave', 'reject leave',
            'view salary advances', 'approve salary advances',
            'view payslips', 'generate payslips',
        ]);
        $financeOfficer->syncPermissions([
            'view payroll', 'generate payroll', 'review payroll', 'approve payroll', 'post payroll',
            'view payslips', 'generate payslips', 'email payslips',
            'view p9 forms', 'generate p9 forms',
        ]);
        $manager->syncPermissions([
            'view employees', 'view attendance', 'view leave', 'approve leave'
        ]);
    }
}
