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

            // ---------------------------------------------------------------
            // Employees
            // ---------------------------------------------------------------
            'employee.view',
            'employee.create',
            'employee.update',
            'employee.delete',

            // ---------------------------------------------------------------
            // Salary (Pay Scale Step assignment & history)
            // ---------------------------------------------------------------
            'employee_salary.view',
            'employee_salary.create',
            'employee_salary.update',

            // ---------------------------------------------------------------
            // CPF Contribution Batches
            // ---------------------------------------------------------------
            'cpf_contribution.view',
            'cpf_contribution.create',
            'cpf_contribution.submit',
            'cpf_contribution.approve', // admin approves & posts ledger
            'cpf_contribution.reverse',

            // ---------------------------------------------------------------
            // CPF Ledger
            // ---------------------------------------------------------------
            'cpf_ledger.view',

            // ---------------------------------------------------------------
            // CPF Advances
            // ---------------------------------------------------------------
            'cpf_advance.view',
            'cpf_advance.create',
            'cpf_advance.submit',
            'cpf_advance.approve',
            'cpf_advance.recovery',

            // ---------------------------------------------------------------
            // CPF Final Settlement
            // ---------------------------------------------------------------
            'cpf_settlement.view',
            'cpf_settlement.create',
            'cpf_settlement.submit',
            'cpf_settlement.approve',

            // ---------------------------------------------------------------
            // Bank Interest Distribution
            // ---------------------------------------------------------------
            'bank_interest.view',
            'bank_interest.create',
            'bank_interest.submit',
            'bank_interest.approve', // NEW — admin approves & posts ledger
            'bank_interest.reverse',

            // ---------------------------------------------------------------
            // Reports
            // ---------------------------------------------------------------
            'report.view',
            'report.export',

            // ---------------------------------------------------------------
            // Settings
            // ---------------------------------------------------------------
            'setting.view',
            'setting.update',

            // ---------------------------------------------------------------
            // Users
            // ---------------------------------------------------------------
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

        // -------------------------------------------------------------------
        // Roles
        // -------------------------------------------------------------------

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

        // Admin gets everything
        $admin->syncPermissions(Permission::all());

        // CPF Officer — can do day-to-day CPF operations, no user/setting management
        $cpfOfficer->syncPermissions([
            'employee.view',
            'employee.create',
            'employee.update',

            'employee_salary.view',
            'employee_salary.create',
            'employee_salary.update',

            'cpf_contribution.view',
            'cpf_contribution.create',
            'cpf_contribution.submit',

            'cpf_ledger.view',

            'cpf_advance.view',
            'cpf_advance.create',
            'cpf_advance.submit',
            'cpf_advance.recovery',

            'cpf_settlement.view',
            'cpf_settlement.create',
            'cpf_settlement.submit',

            'bank_interest.view',
            'bank_interest.create',
            'bank_interest.submit',

            'report.view',
            'report.export',
        ]);

        // Auditor — read-only across all financial modules
        $auditor->syncPermissions([
            'employee.view',

            'employee_salary.view',

            'cpf_contribution.view',

            'cpf_ledger.view',

            'cpf_advance.view',

            'cpf_settlement.view',

            'bank_interest.view',

            'report.view',
            'report.export',
        ]);
    }
}
