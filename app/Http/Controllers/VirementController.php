<?php

namespace App\Http\Controllers;

use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Virement;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Services\VirementService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VirementController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        $query = Virement::with(['fromEconomicCode', 'toEconomicCode', 'fiscalYear', 'creator', 'approver'])
            ->where('fiscal_year_id', $fiscalYearId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $virements = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();

        return view('virements.index', compact('virements', 'fiscalYears'));
    }

    public function create(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $economicCodes = EconomicCode::expense()->active()
            ->whereHas('budgets', fn ($q) => $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'approved'))
            ->orderBy('code')
            ->get();

        return view('virements.create', compact('fiscalYear', 'economicCodes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'from_economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'to_economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference_number' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string'],
        ]);

        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);
        $from = EconomicCode::findOrFail($data['from_economic_code_id']);
        $to = EconomicCode::findOrFail($data['to_economic_code_id']);

        $error = app(VirementService::class)->validate($fiscalYear, $from, $to, $data['amount']);

        if ($error) {
            return back()->withInput()->withErrors(['amount' => $error]);
        }

        $virement = Virement::create($data + [
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        app(AuditService::class)->log('Virement Created', $virement, null, $data);

        return redirect()->route('virements.show', $virement)->with($this->toast('Virement created and pending approval.'));
    }

    public function show(Virement $virement)
    {
        $virement->load(['fromEconomicCode', 'toEconomicCode', 'fiscalYear', 'creator', 'approver']);

        $budgetService = app(BudgetService::class);
        $sourceAvailable = $budgetService->availableBudget($virement->fromEconomicCode, $virement->fiscalYear);

        return view('virements.show', compact('virement', 'sourceAvailable'));
    }

    public function approve(Virement $virement)
    {
        try {
            app(VirementService::class)->approve($virement);
        } catch (\DomainException $e) {
            return back()->with($this->toast($e->getMessage(), 'danger'));
        }

        return back()->with($this->toast('Virement approved. Budgets updated.'));
    }

    public function reject(Virement $virement)
    {
        $virement->update(['status' => 'rejected']);

        app(AuditService::class)->log('Virement Rejected', $virement, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with($this->toast('Virement rejected.', 'danger'));
    }

    public function destroy(Virement $virement)
    {
        if ($virement->isApproved()) {
            return back()->with($this->toast('An approved virement cannot be deleted — it has already adjusted the budgets.', 'danger'));
        }

        $virement->delete();

        app(\App\Services\AuditService::class)->log('Virement Deleted', $virement);

        return redirect()->route('virements.index')->with($this->toast('Virement deleted.'));
    }
}
