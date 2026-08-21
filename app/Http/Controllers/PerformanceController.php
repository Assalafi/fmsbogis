<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Services\BudgetService;
use App\Services\PerformanceService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

        $headings = ['Economic Code', 'Description', 'Total Receipts (₦)', 'Number of Receipts', '% of Revenue'];

        $rows = $codes->map(function ($code) use ($totalRevenue) {
            $pct = $totalRevenue > 0 ? round((float) $code->receipts_total / (float) $totalRevenue * 100, 2) : 0;

            return [
                $code->code,
                $code->name,
                (float) ($code->receipts_total ?? 0),
                $code->receipts_count,
                $pct,
            ];
        })->values()->all();

        $summary = [
            ['label' => 'TOTAL REVENUE', 'value' => '₦'.Money::format($totalRevenue)],
            ['label' => 'REVENUE CODES', 'value' => $codes->count()],
            ['label' => 'FISCAL YEAR', 'value' => 'FY '.($fiscalYear?->name ?? '—')],
            ['label' => 'TOTAL RECEIPTS', 'value' => $codes->sum('receipts_count')],
        ];

        return $this->respond('performance.revenue', 'Revenue Performance', $headings, $rows, $summary, [
            'codes' => $codes,
            'fiscalYear' => $fiscalYear,
            'totalRevenue' => $totalRevenue,
        ]);
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
            $total = $budget ? $budgetService->totalBudget($budget) : '0.00';
            $available = $budget ? $budgetService->availableBudget($code, $fiscalYear) : '0.00';

            return [
                'code' => $code,
                'budget' => $budget,
                'original' => $budget?->original_budget ?? '0.00',
                'total' => $total,
                'paid' => $paid,
                'approved_unpaid' => $approvedUnpaid,
                'available' => $available,
                'utilization' => $total !== '0.00' && $budget ? round((float) Money::div($paid, $total) * 100, 2) : 0,
            ];
        });

        $headings = ['Code', 'Description', 'Account Type', 'Original (₦)', 'Paid (₦)', 'Approved Unpaid (₦)', 'Available (₦)', 'Utilization (%)'];

        $rows = $codes->map(function ($row) {
            return [
                $row['code']->code,
                $row['code']->name,
                ucfirst($row['code']->account_type),
                (float) $row['original'],
                (float) $row['paid'],
                (float) $row['approved_unpaid'],
                (float) $row['available'],
                $row['utilization'],
            ];
        })->values()->all();

        $summary = [
            ['label' => 'ORIGINAL BUDGET', 'value' => '₦'.Money::format($codes->sum(fn ($c) => (float) $c['original']))],
            ['label' => 'TOTAL PAID', 'value' => '₦'.Money::format($codes->sum(fn ($c) => (float) $c['paid']))],
            ['label' => 'AVAILABLE BUDGET', 'value' => '₦'.Money::format($codes->sum(fn ($c) => (float) $c['available']))],
        ];

        return $this->respond('performance.expenditure', 'Expenditure Performance', $headings, $rows, $summary, [
            'codes' => $codes,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    public function economicCodes(Request $request)
    {
        $fiscalYear = ActiveFiscalYear::get();
        $rows = app(PerformanceService::class)->economicCodePerformance($fiscalYear);

        $headings = ['Code', 'Name', 'Type', 'Account Type', 'Original Budget (₦)', 'Receipts (₦)', 'Payments (₦)', 'Available (₦)', 'Performance (%)'];

        $exportRows = collect($rows)->map(function ($row) {
            return [
                $row['code']->code,
                $row['code']->name,
                ucfirst($row['code']->type),
                $row['code']->account_type ? ucfirst($row['code']->account_type) : '—',
                (float) $row['original_budget'],
                (float) $row['receipts'],
                (float) $row['payments'],
                (float) $row['available'],
                $row['performance'] ?? '—',
            ];
        })->values()->all();

        return $this->respond('performance.economic-codes', 'Economic Code Performance', $headings, $exportRows, [], [
            'rows' => $rows,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    public function capital()
    {
        return $this->byAccountType('capital');
    }

    public function overhead()
    {
        return $this->byAccountType('overhead');
    }

    public function personnel()
    {
        return $this->byAccountType('personnel');
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
                $total = $budget ? $budgetService->totalBudget($budget) : '0.00';

                return [
                    'code' => $code,
                    'total' => $total,
                    'paid' => $paid,
                    'available' => $budget ? $budgetService->availableBudget($code, $fiscalYear) : '0.00',
                    'utilization' => $total !== '0.00' && $budget ? round((float) Money::div($paid, $total) * 100, 2) : 0,
                ];
            });

        $headings = ['Economic Code', 'Description', 'Actual (₦)', 'Available (₦)', 'Utilization (%)'];

        $rows = $codes->map(function ($row) {
            return [
                $row['code']->code,
                $row['code']->name,
                (float) $row['paid'],
                (float) $row['available'],
                $row['utilization'],
            ];
        })->values()->all();

        $summary = [
            ['label' => strtoupper($accountType).' PAYMENTS', 'value' => '₦'.Money::format($codes->sum(fn ($c) => (float) $c['paid']))],
            ['label' => strtoupper($accountType).' AVAILABLE', 'value' => '₦'.Money::format($codes->sum(fn ($c) => (float) $c['available']))],
        ];

        return $this->respond('performance.by-type', ucfirst($accountType).' Performance', $headings, $rows, $summary, [
            'codes' => $codes,
            'fiscalYear' => $fiscalYear,
            'accountType' => $accountType,
        ]);
    }

    public function accounts(Request $request)
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

        $headings = ['Account', 'Type', 'Opening (₦)', 'Receipts (₦)', 'Payments (₦)', 'Cashbook Balance (₦)', 'Bank Balance (₦)', 'Difference (₦)', 'Last Reconciled'];

        $rows = $accounts->map(function ($row) {
            return [
                $row['account']->account_name,
                ucfirst($row['account']->account_type),
                (float) $row['opening'],
                (float) $row['receipts'],
                (float) $row['payments'],
                (float) $row['cashbook_balance'],
                (float) $row['bank_balance'],
                (float) $row['cashbook_balance'] - (float) $row['bank_balance'],
                $row['last_reconciled']?->reconciliation_date->format('d M Y') ?? '—',
            ];
        })->values()->all();

        return $this->respond('performance.accounts', 'Account Performance', $headings, $rows, [], [
            'accounts' => $accounts,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    protected function respond(string $view, string $title, array $headings, array $rows, array $summary, array $viewData)
    {
        $export = request('export');

        if ($export === 'excel') {
            return Excel::download(new ArrayExport($headings, $rows, $title), \Illuminate\Support\Str::slug($title).'.xlsx');
        }

        if ($export === 'pdf') {
            $pdf = Pdf::loadView('performance.print', array_merge($viewData, [
                'title' => $title,
                'headings' => $headings,
                'rows' => $rows,
                'summary' => $summary,
                'fiscalYear' => $viewData['fiscalYear'] ?? null,
            ]))->setPaper('a4', 'landscape');

            return $pdf->stream(\Illuminate\Support\Str::slug($title).'.pdf');
        }

        return view($view, $viewData);
    }
}
