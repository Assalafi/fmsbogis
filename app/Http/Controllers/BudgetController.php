<?php

namespace App\Http\Controllers;

use App\Models\BudgetApproval;
use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Services\AuditService;
use App\Services\BudgetService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        $query = EconomicCodeBudget::with(['economicCode', 'fiscalYear', 'creator'])
            ->where('fiscal_year_id', $fiscalYearId);

        if ($request->filled('account_type')) {
            $query->whereHas('economicCode', fn ($q) => $q->where('account_type', $request->account_type));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $budgets = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $budgetService = app(BudgetService::class);

        return view('budgets.index', compact('budgets', 'fiscalYears', 'budgetService'));
    }

    public function create(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $economicCodes = EconomicCode::expense()->active()
            ->whereDoesntHave('budgets', fn ($q) => $q->where('fiscal_year_id', $fiscalYearId))
            ->orderBy('code')
            ->get();

        return view('budgets.create', compact('fiscalYear', 'economicCodes'));
    }

    public function store(Request $request)
    {
        $fiscalYear = FiscalYear::findOrFail($request->fiscal_year_id);

        $data = $request->validate([
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'original_budget' => ['required', 'numeric', 'gt:0'],
            'supplementary_budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $economicCode = EconomicCode::findOrFail($data['economic_code_id']);

        if (! $economicCode->isExpense()) {
            return back()->withInput()->withErrors(['economic_code_id' => 'Only Expense Economic Codes can receive expenditure budgets.']);
        }

        if (! $fiscalYear->isOpen()) {
            return back()->withInput()->withErrors(['fiscal_year_id' => 'The selected Fiscal Year is closed.']);
        }

        $exists = EconomicCodeBudget::where('fiscal_year_id', $data['fiscal_year_id'])
            ->where('economic_code_id', $data['economic_code_id'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['economic_code_id' => 'A budget for this Economic Code already exists for the selected Fiscal Year.']);
        }

        $budget = EconomicCodeBudget::create([
            'fiscal_year_id' => $data['fiscal_year_id'],
            'economic_code_id' => $data['economic_code_id'],
            'original_budget' => $data['original_budget'],
            'supplementary_budget' => $data['supplementary_budget'] ?? 0,
            'revised_budget' => Money::add($data['original_budget'], $data['supplementary_budget'] ?? 0),
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        app(AuditService::class)->log('Budget Created', $budget, null, $data);

        return redirect()->route('budgets.show', $budget)->with($this->toast('Budget created as draft.'));
    }

    public function show(EconomicCodeBudget $budget)
    {
        $budget->load(['economicCode', 'fiscalYear', 'creator', 'approver', 'approvals.approver']);

        $budgetService = app(BudgetService::class);

        $stats = [
            'revised' => $budgetService->revisedBudget($budget),
            'paid' => $budgetService->paidPayments($budget->economicCode, $budget->fiscalYear),
            'approved_unpaid' => $budgetService->approvedUnpaidPayments($budget->economicCode, $budget->fiscalYear),
            'available' => $budgetService->availableBudget($budget->economicCode, $budget->fiscalYear),
        ];

        return view('budgets.show', compact('budget', 'stats'));
    }

    public function submit(EconomicCodeBudget $budget)
    {
        $budget->update(['status' => 'pending']);

        app(AuditService::class)->log('Budget Submitted', $budget, ['status' => 'draft'], ['status' => 'pending']);

        return back()->with($this->toast('Budget submitted for approval.'));
    }

    public function approve(Request $request, EconomicCodeBudget $budget)
    {
        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        BudgetApproval::create([
            'economic_code_budget_id' => $budget->id,
            'action' => 'approved',
            'comment' => $request->comment,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(AuditService::class)->log('Budget Approved', $budget, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with($this->toast('Budget approved. Payments are now allowed.'));
    }

    public function reject(Request $request, EconomicCodeBudget $budget)
    {
        $budget->update(['status' => 'rejected']);

        BudgetApproval::create([
            'economic_code_budget_id' => $budget->id,
            'action' => 'rejected',
            'comment' => $request->comment,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(AuditService::class)->log('Budget Rejected', $budget, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with($this->toast('Budget rejected.', 'danger'));
    }

    public function pending()
    {
        $budgets = EconomicCodeBudget::with(['economicCode', 'fiscalYear', 'creator'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return view('budgets.approval', compact('budgets'));
    }

    /**
     * Show the approved budget upload page.
     */
    public function upload()
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $activeFiscalYearId = ActiveFiscalYear::id();

        return view('budgets.upload', compact('fiscalYears', 'activeFiscalYearId'));
    }

    /**
     * Download the CSV template for budget uploads.
     */
    public function downloadTemplate()
    {
        $csv = "economic_code,amount\n12010101,20000000\n22020101,15000000\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="approved-budget-template.csv"',
        ]);
    }

    /**
     * Import approved budgets from an uploaded CSV/XLSX file.
     */
    public function importBudgetFile(Request $request)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:5120'],
        ]);

        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        $import = new \App\Imports\ApprovedBudgetImport;

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with($this->toast('Could not read the file: '.$e->getMessage(), 'danger'));
        }

        $rows = collect($import->rows);

        if ($rows->isEmpty()) {
            return back()->with($this->toast('No valid rows found. Expected columns: economic_code, amount.', 'warning'));
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $codes = EconomicCode::whereIn('code', $rows->pluck('code')->unique()->values()->all())->get()->keyBy('code');

        foreach ($rows as $index => $row) {
            $line = $index + 2; // 1-based line, accounting for the header row

            $code = $codes->get($row['code']);

            if (! $code) {
                $errors[] = "Row {$line}: Economic code \"{$row['code']}\" not found.";
                $skipped++;

                continue;
            }

            if (! $code->isExpense()) {
                $errors[] = "Row {$line}: Economic code \"{$row['code']}\" is not an Expense code.";
                $skipped++;

                continue;
            }

            if (! $code->isActive()) {
                $errors[] = "Row {$line}: Economic code \"{$row['code']}\" is inactive.";
                $skipped++;

                continue;
            }

            $amount = $row['amount'];

            if ($amount <= 0) {
                $errors[] = "Row {$line}: Amount must be greater than zero for code \"{$row['code']}\".";
                $skipped++;

                continue;
            }

            $exists = EconomicCodeBudget::where('fiscal_year_id', $fiscalYear->id)
                ->where('economic_code_id', $code->id)
                ->exists();

            if ($exists) {
                $errors[] = "Row {$line}: Budget already exists for code \"{$row['code']}\" in FY {$fiscalYear->name}. Skipped.";
                $skipped++;

                continue;
            }

            EconomicCodeBudget::create([
                'fiscal_year_id' => $fiscalYear->id,
                'economic_code_id' => $code->id,
                'original_budget' => $amount,
                'supplementary_budget' => 0,
                'virement_in' => 0,
                'virement_out' => 0,
                'revised_budget' => $amount,
                'status' => 'approved',
                'notes' => 'Imported via budget upload',
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $imported++;
        }

        app(AuditService::class)->log('Approved Budgets Uploaded', null, null, [
            'fiscal_year_id' => $fiscalYear->id,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);

        return back()->with([
            'toast' => [
                'type' => $imported > 0 ? 'success' : 'warning',
                'message' => "Budget upload complete for FY {$fiscalYear->name}: {$imported} imported, {$skipped} skipped.",
            ],
            'upload_errors' => $errors,
        ]);
    }
}
