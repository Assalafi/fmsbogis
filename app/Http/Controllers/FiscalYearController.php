<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\AuditService;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index()
    {
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $activeId = ActiveFiscalYear::id();

        return view('fiscal-years.index', compact('fiscalYears', 'activeId'));
    }

    public function create()
    {
        return view('fiscal-years.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:fiscal_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed'],
        ]);

        $fiscalYear = FiscalYear::create($data);

        app(AuditService::class)->log('Fiscal Year Created', $fiscalYear, null, $data);

        return redirect()->route('fiscal-years.index')->with($this->toast('Fiscal Year created successfully.'));
    }

    public function edit(FiscalYear $fiscalYear)
    {
        return view('fiscal-years.edit', compact('fiscalYear'));
    }

    public function update(Request $request, FiscalYear $fiscalYear)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:fiscal_years,name,'.$fiscalYear->id],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:open,closed'],
        ]);

        $old = $fiscalYear->only(array_keys($data));
        $fiscalYear->update($data);

        app(AuditService::class)->log('Fiscal Year Updated', $fiscalYear, $old, $data);

        return redirect()->route('fiscal-years.index')->with($this->toast('Fiscal Year updated successfully.'));
    }

    public function setActive(FiscalYear $fiscalYear)
    {
        ActiveFiscalYear::set($fiscalYear->id);

        return back()->with($this->toast('Active Fiscal Year set to '.$fiscalYear->name.'.'));
    }

    public function close(FiscalYear $fiscalYear)
    {
        $fiscalYear->update(['status' => 'closed']);

        app(AuditService::class)->log('Fiscal Year Closed', $fiscalYear);

        return back()->with($this->toast('Fiscal Year closed.'));
    }

    public function destroy(FiscalYear $fiscalYear)
    {
        if ($fiscalYear->id === \App\Support\ActiveFiscalYear::id()) {
            return back()->with($this->toast('Cannot delete the active fiscal year. Set another fiscal year active first.', 'danger'));
        }

        $hasActivity = $fiscalYear->budgets()->exists()
            || $fiscalYear->receipts()->exists()
            || $fiscalYear->payments()->exists()
            || $fiscalYear->virements()->exists()
            || \App\Models\CashbookEntry::where('fiscal_year_id', $fiscalYear->id)->exists();

        if ($hasActivity) {
            return back()->with($this->toast('Cannot delete this fiscal year — it has budgets, receipts, payments, virements or cashbook activity.', 'danger'));
        }

        $fiscalYear->delete();

        app(AuditService::class)->log('Fiscal Year Deleted', $fiscalYear);

        return redirect()->route('fiscal-years.index')->with($this->toast('Fiscal year deleted.'));
    }
}
