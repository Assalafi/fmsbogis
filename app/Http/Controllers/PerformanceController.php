<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Services\BudgetService;
use App\Services\PerformanceService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class PerformanceController extends Controller
{
    public function revenue(Request $request)
    {
        $fiscalYear = ActiveFiscalYear::get();

        $query = EconomicCode::revenue()
            ->withSum(['receipts as receipts_total' => function ($q) use ($fiscalYear) {
                $q->where('fiscal_year_id', $fiscalYear?->id)->where('status', 'posted');
            }], 'amount')
            ->withCount(['receipts as receipts_count' => function ($q) use ($fiscalYear) {
                $q->where('fiscal_year_id', $fiscalYear?->id)->where('status', 'posted');
            }]);

        if ($request->filled('economic_code_id')) {
            $query->where('id', $request->economic_code_id);
        }

        $codes = $query->orderByDesc('receipts_total')->get();

        $totalRevenue = $codes->sum('receipts_total');

        return view('performance.revenue', compact('codes', 'fiscalYear', 'totalRevenue'));
    }

    public function expenditure(Request $request)
    {
        $fiscalYear = ActiveFiscalYear::get();
        $budgetService = app(BudgetService::class);

        $query = EconomicCode::expense();

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        $codes = $query->orderBy('code')->get()->map(function (EconomicCode $code) use ($fiscalYear, $budgetService) {
            $budget = $code->budgets()->where('fiscal_year_id', $fiscalYear?->id)->first();
            $paid = $budgetService->paidPayments($code, $fiscalYear);
            $approvedUnpaid = $budgetService->approvedUnpaidPayments($code, $fiscalYear);
            $revised = $budget ? $budgetService->revisedBudget($budget) : '0.00';
            $available = $budget ? $budgetService->availableBudget($code, $fiscalYear) : '0.00';

            return [
                'code' => $code,
                'budget' => $budget,
                'original' => $budget?->original_budget ?? '0.00',
                'revised' => $revised,
                'paid' => $paid,
                'approved_unpaid' => $approvedUnpaid,
                'available' => $available,
                'utilization' => $revised !== '0.00' && $budget ? round((float) \App\Support\Money::div($paid, $revised) * 100, 2) : 0,
            ];
        });

        return view('performance.expenditure', compact('codes', 'fiscalYear'));
    }

    public function economicCodes()
    {
        $fiscalYear = ActiveFiscalYear::get();
        $rows = app(PerformanceService::class)->economicCodePerformance($fiscalYear);

        return view('performance.economic-codes', compact('rows', 'fiscalYear'));
    }

    public function capital()
    {
        return $this->byAccountType('capital');
    }

    public function overhead()
    {
        return $this->byAccountType('overhead');
    }

    protected function byAccountType(string $accountType)
    {
        $fiscalYear = ActiveFiscalYear::get();
        $budgetService = app(BudgetService::class);

        $codes = EconomicCode::expense()->where('account_type', $accountType)
            ->orderBy('code')
            ->get()
            ->map(function (EconomicCode $code) use ($fiscalYear, $budgetService) {
                $budget = $code->budgets()->where('fiscal_year_id', $fiscalYear?->id)->first();
                $paid = $budgetService->paidPayments($code, $fiscalYear);
                $revised = $budget ? $budgetService->revisedBudget($budget) : '0.00';

                return [
                    'code' => $code,
                    'revised' => $revised,
                    'paid' => $paid,
                    'available' => $budget ? $budgetService->availableBudget($code, $fiscalYear) : '0.00',
                    'utilization' => $revised !== '0.00' && $budget ? round((float) \App\Support\Money::div($paid, $revised) * 100, 2) : 0,
                ];
            });

        return view('performance.by-type', compact('codes', 'fiscalYear', 'accountType'));
    }

    public function accounts()
    {
        $fiscalYear = ActiveFiscalYear::get();

        $accounts = Account::orderBy('account_name')->get()->map(function (Account $account) use ($fiscalYear) {
            $last = $account->reconciliations()->latest('reconciliation_date')->first();
            $bankBalance = $last ? $last->bank_statement_balance : '0.00';

            return [
                'account' => $account,
                'opening' => $account->opening_balance,
                'receipts' => $account->receipts()->where('fiscal_year_id', $fiscalYear?->id)->where('status', 'posted')->sum('amount'),
                'payments' => $account->payments()->where('fiscal_year_id', $fiscalYear?->id)->where('status', 'paid')->sum('amount'),
                'cashbook_balance' => app(\App\Services\CashbookService::class)->closingBalance($account, $fiscalYear),
                'bank_balance' => $bankBalance,
                'last_reconciled' => $last,
            ];
        });

        return view('performance.accounts', compact('accounts', 'fiscalYear'));
    }
}
