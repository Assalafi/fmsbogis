<?php

namespace App\Http\Controllers;

use App\Models\EBudgetSyncRun;
use App\Models\FiscalYear;
use App\Models\Virement;
use App\Support\ActiveFiscalYear;
use Illuminate\Http\Request;

class VirementController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id')
            ? $request->fiscal_year_id
            : ActiveFiscalYear::id();

        $query = Virement::with(['fromEconomicCode', 'toEconomicCode', 'fiscalYear'])
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('source_system', 'ebudget')
            ->where('source_active', true)
            ->where('status', 'approved');

        if ($request->filled('direction')) {
            $mdaCode = (string) config('services.ebudget.mda_code');

            if ($request->direction === 'in') {
                $query->where('to_mda_code', $mdaCode);
            } elseif ($request->direction === 'out') {
                $query->where('from_mda_code', $mdaCode);
            }
        }

        $virements = $query->orderBy('date', 'desc')->orderBy('source_id', 'desc')
            ->paginate(25)
            ->withQueryString();
        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $selectedFiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);
        $lastSync = $selectedFiscalYear
            ? EBudgetSyncRun::where('fiscal_year_id', $selectedFiscalYear->id)->latest('started_at')->first()
            : null;

        return view('virements.index', compact(
            'virements',
            'fiscalYears',
            'selectedFiscalYear',
            'lastSync'
        ));
    }

    public function show(Virement $virement)
    {
        abort_unless($virement->source_system === 'ebudget' && $virement->source_active && $virement->isApproved(), 404);

        $virement->load(['fromEconomicCode', 'toEconomicCode', 'fiscalYear']);

        return view('virements.show', compact('virement'));
    }
}
