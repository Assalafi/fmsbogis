<?php

namespace App\Http\Controllers;

use App\Models\BudgetClearance;
use App\Models\EBudgetSyncRun;
use App\Models\FiscalYear;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class BudgetClearanceController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id')
            ? $request->fiscal_year_id
            : ActiveFiscalYear::id();

        $query = BudgetClearance::with(['economicCode', 'fiscalYear'])
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('source_system', 'ebudget')
            ->where('source_active', true)
            ->where('status', 'approved');

        if ($request->filled('approval_type')) {
            $query->where('approval_type', $request->approval_type);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('payee_name', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('source_id', 'like', "%{$search}%")
                    ->orWhereHas('economicCode', function ($economic) use ($search) {
                        $economic->where('code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $totalApproved = (clone $query)->sum('amount');
        $clearances = $query->orderByDesc('approved_at')
            ->orderByDesc('source_id')
            ->paginate(25)
            ->withQueryString();
        $fiscalYears = FiscalYear::orderByDesc('start_date')->get();
        $selectedFiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);
        $lastSync = $selectedFiscalYear
            ? EBudgetSyncRun::where('fiscal_year_id', $selectedFiscalYear->id)->latest('started_at')->first()
            : null;
        $approvalTypes = BudgetClearance::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('source_system', 'ebudget')
            ->where('source_active', true)
            ->where('status', 'approved')
            ->whereNotNull('approval_type')
            ->where('approval_type', '!=', '')
            ->distinct()
            ->orderBy('approval_type')
            ->pluck('approval_type');

        return view('budget-clearances.index', compact(
            'clearances',
            'fiscalYears',
            'selectedFiscalYear',
            'lastSync',
            'approvalTypes',
            'totalApproved'
        ));
    }

    public function show(BudgetClearance $budgetClearance)
    {
        abort_unless(
            $budgetClearance->source_system === 'ebudget'
                && $budgetClearance->source_active
                && $budgetClearance->isApproved(),
            404
        );

        $budgetClearance->load(['economicCode', 'fiscalYear']);

        return view('budget-clearances.show', compact('budgetClearance'));
    }
}
