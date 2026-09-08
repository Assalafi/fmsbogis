<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\EBudgetSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BudgetSyncController extends Controller
{
    public function store(Request $request, EBudgetSyncService $syncService)
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
        ]);

        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        try {
            $run = $syncService->sync($fiscalYear, auth()->id());

            return back()->with($this->toast($run->message));
        } catch (Throwable $e) {
            Log::warning('eBudget synchronisation failed.', [
                'fiscal_year' => $fiscalYear->name,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with($this->toast('eBudget sync failed: '.$e->getMessage(), 'danger'));
        }
    }
}
