<?php

namespace App\Http\Controllers;

use App\Models\EBudgetSyncRun;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Services\BudgetService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id')
            ? $request->fiscal_year_id
            : ActiveFiscalYear::id();

        $query = EconomicCodeBudget::with(['economicCode', 'fiscalYear'])
            ->where('economic_code_budgets.fiscal_year_id', $fiscalYearId)
            ->authoritative();

        if ($request->filled('account_type')) {
            $query->whereHas('economicCode', fn ($q) => $q->where('account_type', $request->account_type));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->whereHas('economicCode', function ($economic) use ($search) {
                $economic->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $budgets = $query
            ->join('economic_codes', 'economic_codes.id', '=', 'economic_code_budgets.economic_code_id')
            ->select('economic_code_budgets.*')
            ->orderBy('economic_codes.code')
            ->paginate(25)
            ->withQueryString();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $selectedFiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);
        $lastSync = $selectedFiscalYear
            ? EBudgetSyncRun::where('fiscal_year_id', $selectedFiscalYear->id)->latest('started_at')->first()
            : null;
        $budgetService = app(BudgetService::class);

        return view('budgets.index', compact(
            'budgets',
            'fiscalYears',
            'selectedFiscalYear',
            'lastSync',
            'budgetService'
        ));
    }

    public function show(EconomicCodeBudget $budget)
    {
        abort_unless($budget->source_system === 'ebudget' && $budget->source_active && $budget->isApproved(), 404);

        $budget->load(['economicCode', 'fiscalYear']);

        $budgetService = app(BudgetService::class);
        $stats = [
            'total' => $budgetService->totalBudget($budget),
            'paid' => $budgetService->paidPayments($budget->economicCode, $budget->fiscalYear),
            'approved_unpaid' => $budgetService->approvedUnpaidPayments($budget->economicCode, $budget->fiscalYear),
            'available' => $budgetService->availableBudget($budget->economicCode, $budget->fiscalYear),
        ];

        return view('budgets.show', compact('budget', 'stats'));
    }
}
