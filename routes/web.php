<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CashbookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EconomicCodeController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VirementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'permission:dashboard.view'])->group(function () {
    Route::get('/', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('permission:accounts.view')->prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('index');
        Route::get('create', [AccountController::class, 'create'])->name('create')->middleware('permission:accounts.create');
        Route::post('/', [AccountController::class, 'store'])->name('store')->middleware('permission:accounts.create');
        Route::get('{account}', [AccountController::class, 'show'])->name('show');
        Route::get('{account}/edit', [AccountController::class, 'edit'])->name('edit')->middleware('permission:accounts.update');
        Route::put('{account}', [AccountController::class, 'update'])->name('update')->middleware('permission:accounts.update');
        Route::delete('{account}', [AccountController::class, 'destroy'])->name('destroy')->middleware('permission:accounts.update');
    });

    Route::middleware('permission:economic_codes.view')->prefix('economic-codes')->name('economic-codes.')->group(function () {
        Route::get('/', [EconomicCodeController::class, 'index'])->name('index');
        Route::get('create', [EconomicCodeController::class, 'create'])->name('create')->middleware('permission:economic_codes.create');
        Route::post('/', [EconomicCodeController::class, 'store'])->name('store')->middleware('permission:economic_codes.create');
        Route::get('{economicCode}', [EconomicCodeController::class, 'show'])->name('show');
        Route::get('{economicCode}/edit', [EconomicCodeController::class, 'edit'])->name('edit')->middleware('permission:economic_codes.update');
        Route::put('{economicCode}', [EconomicCodeController::class, 'update'])->name('update')->middleware('permission:economic_codes.update');
    });

    Route::middleware('permission:fiscal_years.view')->prefix('fiscal-years')->name('fiscal-years.')->group(function () {
        Route::get('/', [FiscalYearController::class, 'index'])->name('index');
        Route::get('create', [FiscalYearController::class, 'create'])->name('create')->middleware('permission:fiscal_years.create');
        Route::post('/', [FiscalYearController::class, 'store'])->name('store')->middleware('permission:fiscal_years.create');
        Route::get('{fiscalYear}/edit', [FiscalYearController::class, 'edit'])->name('edit')->middleware('permission:fiscal_years.update');
        Route::put('{fiscalYear}', [FiscalYearController::class, 'update'])->name('update')->middleware('permission:fiscal_years.update');
        Route::post('{fiscalYear}/set-active', [FiscalYearController::class, 'setActive'])->name('set-active');
        Route::post('{fiscalYear}/close', [FiscalYearController::class, 'close'])->name('close')->middleware('permission:fiscal_years.update');
    });

    Route::middleware('permission:budgets.view')->prefix('budgets')->name('budgets.')->group(function () {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::get('pending', [BudgetController::class, 'pending'])->name('pending');
        Route::get('create', [BudgetController::class, 'create'])->name('create')->middleware('permission:budgets.create');
        Route::post('/', [BudgetController::class, 'store'])->name('store')->middleware('permission:budgets.create');
        Route::get('{budget}', [BudgetController::class, 'show'])->name('show');
        Route::post('{budget}/submit', [BudgetController::class, 'submit'])->name('submit')->middleware('permission:budgets.create');
        Route::post('{budget}/approve', [BudgetController::class, 'approve'])->name('approve')->middleware('permission:budgets.approve');
        Route::post('{budget}/reject', [BudgetController::class, 'reject'])->name('reject')->middleware('permission:budgets.approve');
    });

    Route::middleware('permission:virements.view')->prefix('virements')->name('virements.')->group(function () {
        Route::get('/', [VirementController::class, 'index'])->name('index');
        Route::get('create', [VirementController::class, 'create'])->name('create')->middleware('permission:virements.create');
        Route::post('/', [VirementController::class, 'store'])->name('store')->middleware('permission:virements.create');
        Route::get('{virement}', [VirementController::class, 'show'])->name('show');
        Route::post('{virement}/approve', [VirementController::class, 'approve'])->name('approve')->middleware('permission:virements.approve');
        Route::post('{virement}/reject', [VirementController::class, 'reject'])->name('reject')->middleware('permission:virements.approve');
    });

    Route::middleware('permission:receipts.view')->prefix('receipts')->name('receipts.')->group(function () {
        Route::get('/', [ReceiptController::class, 'index'])->name('index');
        Route::get('create', [ReceiptController::class, 'create'])->name('create')->middleware('permission:receipts.create');
        Route::post('/', [ReceiptController::class, 'store'])->name('store')->middleware('permission:receipts.create');
        Route::get('{receipt}', [ReceiptController::class, 'show'])->name('show');
        Route::get('{receipt}/pdf', [\App\Http\Controllers\BogisCashReceiptPdfController::class, 'show'])->name('pdf');
        Route::get('{receipt}/edit', [ReceiptController::class, 'edit'])->name('edit')->middleware('permission:receipts.create');
        Route::put('{receipt}', [ReceiptController::class, 'update'])->name('update')->middleware('permission:receipts.create');
        Route::post('{receipt}/approve', [ReceiptController::class, 'approve'])->name('approve')->middleware('permission:receipts.approve');
        Route::post('{receipt}/post', [ReceiptController::class, 'post'])->name('post')->middleware('permission:receipts.approve');
        Route::post('{receipt}/reverse', [ReceiptController::class, 'reverse'])->name('reverse')->middleware('permission:receipts.approve');
        Route::get('{receipt}/print', [ReceiptController::class, 'print'])->name('print');
    });

    Route::middleware('permission:payments.view')->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('approval', [PaymentController::class, 'approval'])->name('approval');
        Route::get('create', [PaymentController::class, 'create'])->name('create')->middleware('permission:payments.create');
        Route::post('/', [PaymentController::class, 'store'])->name('store')->middleware('permission:payments.create');
        Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('{payment}/edit', [PaymentController::class, 'edit'])->name('edit')->middleware('permission:payments.create');
        Route::put('{payment}', [PaymentController::class, 'update'])->name('update')->middleware('permission:payments.create');
        Route::post('{payment}/approve', [PaymentController::class, 'approve'])->name('approve')->middleware('permission:payments.approve');
        Route::post('{payment}/reject', [PaymentController::class, 'reject'])->name('reject')->middleware('permission:payments.approve');
        Route::post('{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('mark-paid')->middleware('permission:payments.mark_paid');
        Route::post('{payment}/reverse', [PaymentController::class, 'reverse'])->name('reverse')->middleware('permission:payments.approve');
        Route::get('{payment}/print', [PaymentController::class, 'print'])->name('print');
    });

    Route::middleware('permission:cashbook.view')->prefix('cashbook')->name('cashbook.')->group(function () {
        Route::get('{account}', [CashbookController::class, 'show'])->name('show');
        Route::get('{account}/print', [CashbookController::class, 'print'])->name('print');
    });

    Route::middleware('permission:bank_statements.view')->prefix('bank-statements')->name('bank-statements.')->group(function () {
        Route::get('/', [BankStatementController::class, 'index'])->name('index');
        Route::get('create', [BankStatementController::class, 'create'])->name('create')->middleware('permission:bank_statements.create');
        Route::post('/', [BankStatementController::class, 'store'])->name('store')->middleware('permission:bank_statements.create');
        Route::get('{statement}', [BankStatementController::class, 'show'])->name('show');
        Route::delete('{statement}', [BankStatementController::class, 'destroy'])->name('destroy')->middleware('permission:bank_statements.create');
    });

    Route::middleware('permission:bank_reconciliation.view')->prefix('reconciliations')->name('reconciliations.')->group(function () {
        Route::get('/', [BankReconciliationController::class, 'index'])->name('index');
        Route::get('create', [BankReconciliationController::class, 'create'])->name('create')->middleware('permission:bank_reconciliation.create');
        Route::post('/', [BankReconciliationController::class, 'store'])->name('store')->middleware('permission:bank_reconciliation.create');
        Route::get('{reconciliation}', [BankReconciliationController::class, 'show'])->name('show');
        Route::post('{reconciliation}/match', [BankReconciliationController::class, 'match'])->name('match')->middleware('permission:bank_reconciliation.create');
        Route::delete('{reconciliation}/items/{item}/unmatch', [BankReconciliationController::class, 'unmatch'])->name('unmatch')->middleware('permission:bank_reconciliation.create');
        Route::post('{reconciliation}/entries/{entry}/outstanding', [BankReconciliationController::class, 'markOutstanding'])->name('outstanding')->middleware('permission:bank_reconciliation.create');
        Route::post('{reconciliation}/lines/{lineId}/bank-only', [BankReconciliationController::class, 'markBankOnly'])->name('bank-only')->middleware('permission:bank_reconciliation.create');
        Route::post('{reconciliation}/adjustments', [BankReconciliationController::class, 'addAdjustment'])->name('adjustments')->middleware('permission:bank_reconciliation.create');
        Route::post('{reconciliation}/approve', [BankReconciliationController::class, 'approve'])->name('approve')->middleware('permission:bank_reconciliation.approve');
        Route::get('{reconciliation}/print', [BankReconciliationController::class, 'print'])->name('print');
    });

    Route::middleware('permission:performance.view')->prefix('performance')->name('performance.')->group(function () {
        Route::get('revenue', [PerformanceController::class, 'revenue'])->name('revenue');
        Route::get('expenditure', [PerformanceController::class, 'expenditure'])->name('expenditure');
        Route::get('economic-codes', [PerformanceController::class, 'economicCodes'])->name('economic-codes');
        Route::get('capital', [PerformanceController::class, 'capital'])->name('capital');
        Route::get('overhead', [PerformanceController::class, 'overhead'])->name('overhead');
        Route::get('accounts', [PerformanceController::class, 'accounts'])->name('accounts');
    });

    Route::middleware('permission:reports.view')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('{report}', [ReportController::class, 'show'])->name('show');
    });

    Route::middleware('permission:audit_logs.view')->prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('{auditLog}', [AuditLogController::class, 'show'])->name('show');
    });

    Route::middleware('permission:users.view')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.update');
        Route::put('{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.update');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.update');
    });

    Route::middleware('permission:roles.view')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::put('{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:roles.update');
    });

    Route::middleware('permission:settings.view')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update')->middleware('permission:settings.update');
        Route::post('sync-forms-payments', [SettingsController::class, 'syncFormsPayments'])->name('sync-forms-payments')->middleware('permission:settings.update');
    });

    // AJAX endpoints for forms
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('economic-codes/receipt', [EconomicCodeController::class, 'receiptCodes'])->name('economic-codes.receipt');
        Route::get('economic-codes/payment', [EconomicCodeController::class, 'paymentCodes'])->name('economic-codes.payment');
        Route::get('economic-codes/{economicCode}/budget', [EconomicCodeController::class, 'availableBudget'])->name('economic-codes.budget');
        Route::get('fiscal-year/{fiscalYear}/activate', [FiscalYearController::class, 'setActive'])->name('fiscal-year.activate');
    });
});
