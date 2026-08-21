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

    /**
     * Show the economic code upload page.
     */
    public function upload()
    {
        return view('economic-codes.upload');
    }

    /**
     * Download the CSV template for economic code uploads.
     */
    public function downloadTemplate()
    {
        $csv = "code,name,description\n12010101,Ground Rent,Revenue from ground rent\n22020101,Local Travel,Overhead travel expenses\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="economic-code-template.csv"',
        ]);
    }

    /**
     * Import economic codes from an uploaded CSV/XLSX file.
     * All codes in the file are created with the same type.
     */
    public function importCodes(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:revenue,expense'],
            'account_type' => ['nullable', 'in:capital,overhead'],
            'status' => ['required', 'in:active,inactive'],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt', 'max:5120'],
        ]);

        if ($data['type'] === 'expense' && empty($data['account_type'])) {
            return back()->with($this->toast('Account Type is required when importing Expense Economic Codes.', 'danger'));
        }

        $import = new \App\Imports\EconomicCodeImport;

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with($this->toast('Could not read the file: '.$e->getMessage(), 'danger'));
        }

        $rows = collect($import->rows);

        if ($rows->isEmpty()) {
            return back()->with($this->toast('No valid rows found. Expected columns: code, name, description.', 'warning'));
        }

        $typeLabel = ucfirst($data['type']);
        $accountType = $data['type'] === 'revenue' ? null : $data['account_type'];

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $existing = EconomicCode::whereIn('code', $rows->pluck('code')->unique()->values()->all())->pluck('code')->flip();

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            if (strlen($row['code']) === 0) {
                continue;
            }

            if ($existing->has($row['code'])) {
                $errors[] = "Row {$line}: Economic code \"{$row['code']}\" already exists. Skipped.";
                $skipped++;

                continue;
            }

            if (strlen($row['name']) === 0) {
                $errors[] = "Row {$line}: Name is required for code \"{$row['code']}\".";
                $skipped++;

                continue;
            }

            EconomicCode::create([
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => $data['type'],
                'account_type' => $accountType,
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            $existing->put($row['code'], true);
            $imported++;
        }

        app(AuditService::class)->log('Economic Codes Uploaded', null, null, [
            'type' => $data['type'],
            'account_type' => $accountType,
            'imported' => $imported,
            'skipped' => $skipped,
        ]);

        return back()->with([
            'toast' => [
                'type' => $imported > 0 ? 'success' : 'warning',
                'message' => "Economic Code upload complete ({$typeLabel}): {$imported} imported, {$skipped} skipped.",
            ],
            'upload_errors' => $errors,
        ]);
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
