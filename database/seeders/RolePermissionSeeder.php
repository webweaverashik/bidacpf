<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Employee
            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',

            // Salary
            'salary.view',
            'salary.create',
            'salary.update',

            // Contribution
            'contribution.view',
            'contribution.create',
            'contribution.post',

            // Ledger
            'ledger.view',

            // Advance
            'advance.view',
            'advance.create',
            'advance.approve',
            'advance.recovery',

            // Interest
            'interest.view',
            'interest.distribute',

            // Reports
            'report.view',
            'report.export',

            // Settings
            'setting.view',
            'setting.update',

            // Users
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name'       => 'Admin',
            'guard_name' => 'web',
        ]);

        $cpfOfficer = Role::firstOrCreate([
            'name'       => 'CPF Officer',
            'guard_name' => 'web',
        ]);

        $auditor = Role::firstOrCreate([
            'name'       => 'Auditor',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions(Permission::all());

        $cpfOfficer->syncPermissions(['employee.view', 'employee.create', 'employee.update', 'salary.view', 'contribution.view', 'contribution.create', 'contribution.post', 'ledger.view', 'advance.view', 'advance.create', 'advance.recovery', 'interest.view', 'report.view', 'report.export']);

        $auditor->syncPermissions(['employee.view', 'salary.view', 'ledger.view', 'advance.view', 'interest.view', 'report.view']);
    }
}
