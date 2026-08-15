<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\EconomicCodeBudget;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Virement;
use App\Services\PerformanceService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $fiscalYear = ActiveFiscalYear::get();

        if (! $fiscalYear) {
            return view('dashboard.index', [
                'fiscalYear' => null,
                'totals' => [],
                'monthly' => [],
                'pending' => [],
                'reconciliation' => [],
            ]);
        }

        $performance = app(PerformanceService::class);

        $totals = $performance->totals($fiscalYear);
        $monthly = $performance->monthlySeries($fiscalYear);

        $pending = [
            'budgets' => EconomicCodeBudget::where('status', 'pending')->count(),
            'virements' => Virement::where('status', 'pending')->count(),
            'receipts' => Receipt::where('status', 'pending')->count(),
            'payments' => Payment::where('status', 'pending')->count(),
            'reconciliations' => BankReconciliation::where('status', 'draft')->count(),
        ];

        $unmatched = BankStatementLine::where('match_status', 'unmatched')->count();

        $reconciliationStatus = Account::active()->get()->map(function (Account $account) {
            $last = $account->reconciliations()->latest('reconciliation_date')->first();

            return [
                'account' => $account,
                'last_reconciled' => $last,
                'status' => $last ? $last->status : 'never',
            ];
        });

        return view('dashboard.index', compact('fiscalYear', 'totals', 'monthly', 'pending', 'reconciliationStatus', 'unmatched'));
    }
}
