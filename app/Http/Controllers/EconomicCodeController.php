<?php

namespace App\Http\Controllers;

use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\Account;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Illuminate\Http\Request;

class EconomicCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = EconomicCode::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $economicCodes = $query->orderBy('code')->paginate(20)->withQueryString();

        return view('economic-codes.index', compact('economicCodes'));
    }

    public function create()
    {
        return view('economic-codes.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:economic_codes,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:revenue,expense'],
            'account_type' => ['nullable', 'in:capital,overhead'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($data['type'] === 'revenue') {
            $data['account_type'] = null;
        }

        if ($data['type'] === 'expense' && empty($data['account_type'])) {
            return back()->withInput()->withErrors(['account_type' => 'Account Type is required for Expense Economic Codes.']);
        }

        $economicCode = EconomicCode::create($data + ['created_by' => auth()->id()]);

        app(AuditService::class)->log('Economic Code Created', $economicCode, null, $data);

        return redirect()->route('economic-codes.index')->with($this->toast('Economic Code created successfully.'));
    }

    public function show(EconomicCode $economicCode)
    {
        $fiscalYear = ActiveFiscalYear::get();
        $budgetService = app(BudgetService::class);

        $budget = $economicCode->budgets()->where('fiscal_year_id', $fiscalYear?->id)->first();

        return view('economic-codes.show', compact('economicCode', 'budget', 'budgetService', 'fiscalYear'));
    }

    public function edit(EconomicCode $economicCode)
    {
        return view('economic-codes.edit', compact('economicCode'));
    }

    public function update(Request $request, EconomicCode $economicCode)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:economic_codes,code,'.$economicCode->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:revenue,expense'],
            'account_type' => ['nullable', 'in:capital,overhead'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($data['type'] === 'revenue') {
            $data['account_type'] = null;
        }

        $old = $economicCode->only(array_keys($data));
        $economicCode->update($data);

        app(AuditService::class)->log('Economic Code Updated', $economicCode, $old, $data);

        return redirect()->route('economic-codes.index')->with($this->toast('Economic Code updated successfully.'));
    }

    public function receiptCodes()
    {
        return EconomicCode::revenue()->active()->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function paymentCodes(Request $request)
    {
        $request->validate(['account_id' => ['required', 'uuid', 'exists:accounts,id']]);

        $account = Account::findOrFail($request->account_id);
        $fiscalYear = ActiveFiscalYear::get();

        return EconomicCode::expense()
            ->active()
            ->where('account_type', $account->account_type)
            ->whereHas('budgets', function ($q) use ($fiscalYear) {
                $q->where('fiscal_year_id', $fiscalYear?->id)
                    ->where('status', 'approved');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'account_type']);
    }

    public function availableBudget(EconomicCode $economicCode)
    {
        $fiscalYear = ActiveFiscalYear::get();

        if (! $fiscalYear) {
            return response()->json(['error' => 'No open fiscal year'], 422);
        }

        $budgetService = app(BudgetService::class);
        $budget = $economicCode->budgets()->where('fiscal_year_id', $fiscalYear->id)->first();

        return response()->json([
            'original_budget' => Money::format($budget?->original_budget),
            'supplementary_budget' => Money::format($budget?->supplementary_budget),
            'virement_in' => Money::format($budget?->virement_in),
            'virement_out' => Money::format($budget?->virement_out),
            'revised_budget' => Money::format($budget && $budget->status === 'approved' ? $budgetService->revisedBudget($budget) : 0),
            'paid_payments' => Money::format($budgetService->paidPayments($economicCode, $fiscalYear)),
            'approved_unpaid' => Money::format($budgetService->approvedUnpaidPayments($economicCode, $fiscalYear)),
            'available_budget' => Money::format($budgetService->availableBudget($economicCode, $fiscalYear)),
        ]);
    }
}
