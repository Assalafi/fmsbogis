<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Finance permission catalogue
    |--------------------------------------------------------------------------
    |
    | This is the single list used by the role screen and permission seeder.
    | Keep permissions grouped so administrators can understand what they are
    | granting without needing to know the internal permission names.
    |
    */
    'groups' => [
        'General' => [
            'dashboard.view' => 'Access the dashboard and sign in to the finance system',
        ],
        'Accounts & Master Data' => [
            'accounts.view' => 'View accounts',
            'accounts.create' => 'Create accounts',
            'accounts.update' => 'Edit and manage accounts',
            'economic_codes.view' => 'View economic codes',
            'economic_codes.create' => 'Create and upload economic codes',
            'economic_codes.update' => 'Edit and manage economic codes',
            'fiscal_years.view' => 'View fiscal years',
            'fiscal_years.create' => 'Create fiscal years',
            'fiscal_years.update' => 'Edit, activate, close, and manage fiscal years',
        ],
        'Budget Management' => [
            'budgets.view' => 'View approved budgets synchronised from eBudget',
            'budgets.sync' => 'Synchronise approved budgets and virements from eBudget',
            'virements.view' => 'View approved virements synchronised from eBudget',
        ],
        'Receipts & Payments' => [
            'receipts.view' => 'View, print, and download receipts',
            'receipts.create' => 'Create, edit, and manage receipts',
            'receipts.approve' => 'Approve, post, or reverse receipts',
            'payments.view' => 'View and print payments',
            'payments.create' => 'Create, edit, and manage payments',
            'payments.approve' => 'Approve, reject, or reverse payments',
            'payments.mark_paid' => 'Mark approved payments as paid',
        ],
        'Banking & Reconciliation' => [
            'cashbook.view' => 'View, print, and export cashbooks',
            'bank_statements.view' => 'View bank statement records and attachments',
            'bank_statements.create' => 'Create, edit, attach, and manage bank statements',
            'bank_reconciliation.view' => 'View, print, and export reconciliations',
            'bank_reconciliation.create' => 'Create and manage reconciliation adjustments',
            'bank_reconciliation.approve' => 'Approve completed reconciliations',
        ],
        'Performance & Reports' => [
            'performance.view' => 'View financial performance dashboards',
            'reports.view' => 'View financial reports',
            'reports.export' => 'Export financial reports to PDF or Excel',
        ],
        'Users & Access Control' => [
            'users.view' => 'View system users',
            'users.create' => 'Create users and assign permitted roles',
            'users.update' => 'Edit, disable, and manage users',
            'roles.view' => 'View roles and their permissions',
            'roles.create' => 'Create new roles',
            'roles.update' => 'Rename roles and change their permissions',
            'roles.delete' => 'Delete unused custom roles',
        ],
        'System Administration' => [
            'audit_logs.view' => 'View audit logs',
            'settings.view' => 'View system settings',
            'settings.update' => 'Change system settings and run synchronisation',
        ],
    ],

    'protected_roles' => [
        'Super Admin',
    ],
];
