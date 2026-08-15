<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    protected array $permissions = [
        'dashboard.view',
        'accounts.view', 'accounts.create', 'accounts.update',
        'economic_codes.view', 'economic_codes.create', 'economic_codes.update',
        'fiscal_years.view', 'fiscal_years.create', 'fiscal_years.update',
        'budgets.view', 'budgets.create', 'budgets.approve',
        'virements.view', 'virements.create', 'virements.approve', 'virements.cross_type',
        'receipts.view', 'receipts.create', 'receipts.approve',
        'payments.view', 'payments.create', 'payments.approve', 'payments.mark_paid',
        'cashbook.view',
        'bank_statements.view', 'bank_statements.create',
        'bank_reconciliation.view', 'bank_reconciliation.create', 'bank_reconciliation.approve',
        'performance.view',
        'reports.view', 'reports.export',
        'audit_logs.view',
        'users.view', 'users.create', 'users.update',
        'roles.view', 'roles.update',
        'settings.view', 'settings.update',
    ];

    protected array $roles = [
        'Super Admin' => '*',
        'Finance Admin' => [
            'dashboard.view',
            'accounts.view', 'accounts.create', 'accounts.update',
            'economic_codes.view', 'economic_codes.create', 'economic_codes.update',
            'fiscal_years.view', 'fiscal_years.create', 'fiscal_years.update',
            'budgets.view', 'budgets.create', 'budgets.approve',
            'virements.view', 'virements.create', 'virements.approve',
            'receipts.view', 'receipts.create', 'receipts.approve',
            'payments.view', 'payments.create', 'payments.approve', 'payments.mark_paid',
            'cashbook.view',
            'bank_statements.view', 'bank_statements.create',
            'bank_reconciliation.view', 'bank_reconciliation.create', 'bank_reconciliation.approve',
            'performance.view',
            'reports.view', 'reports.export',
            'audit_logs.view',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.update',
            'settings.view', 'settings.update',
        ],
        'Accountant' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'fiscal_years.view',
            'budgets.view',
            'virements.view',
            'receipts.view', 'receipts.create', 'receipts.approve',
            'payments.view', 'payments.create',
            'cashbook.view',
            'bank_statements.view', 'bank_statements.create',
            'bank_reconciliation.view', 'bank_reconciliation.create',
            'performance.view',
            'reports.view', 'reports.export',
            'audit_logs.view',
        ],
        'Budget Officer' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'fiscal_years.view',
            'budgets.view', 'budgets.create',
            'virements.view', 'virements.create',
            'performance.view',
            'reports.view',
        ],
        'Revenue Officer' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'receipts.view', 'receipts.create',
            'cashbook.view',
            'performance.view',
        ],
        'Payment Officer' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'budgets.view',
            'payments.view', 'payments.create',
            'cashbook.view',
            'performance.view',
        ],
        'Reconciliation Officer' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'cashbook.view',
            'bank_statements.view', 'bank_statements.create',
            'bank_reconciliation.view', 'bank_reconciliation.create',
            'performance.view',
        ],
        'Auditor' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'budgets.view',
            'virements.view',
            'receipts.view',
            'payments.view',
            'cashbook.view',
            'bank_statements.view',
            'bank_reconciliation.view',
            'performance.view',
            'reports.view',
            'audit_logs.view',
        ],
        'Viewer' => [
            'dashboard.view',
            'accounts.view',
            'economic_codes.view',
            'budgets.view',
            'receipts.view',
            'payments.view',
            'cashbook.view',
            'performance.view',
            'reports.view',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach ($this->roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions === '*') {
                $role->syncPermissions($this->permissions);
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
