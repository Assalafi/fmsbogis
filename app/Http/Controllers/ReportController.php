<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Virement;
use App\Services\BudgetService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function show(Request $request, string $report)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $report = str_replace('-', '_', $report);

        $method = 'report'.ucfirst(\Illuminate\Support\Str::studly($report));

        if (! method_exists($this, $method)) {
            abort(404, 'Report not found.');
        }

        $data = $this->{$method}($request, $fiscalYear);

        if ($request->filled('export') && $request->export === 'excel') {
            return $this->exportExcel($report, $data);
        }

        $view = 'reports.'.str_replace('_', '-', $report);

        if ($request->filled('export') && $request->export === 'pdf') {
            $pdf = Pdf::loadView($view, array_merge($data, ['fiscalYear' => $fiscalYear]))->setPaper('a4', 'landscape');

            return $pdf->stream($report.'.pdf');
        }

        return view($view, array_merge($data, ['fiscalYear' => $fiscalYear]));
    }

    protected function receiptRegister(Request $request, ?FiscalYear $fiscalYear): array
    {
        $query = Receipt::with(['account', 'economicCode'])
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_of_transaction', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date_of_transaction', '<=', $request->date_to));

        $receipts = $query->orderBy('date_of_transaction')->get();
        $total = Money::normalize($receipts->sum('amount'));

        return compact('receipts', 'total');
    }

    protected function paymentRegister(Request $request, ?FiscalYear $fiscalYear): array
    {
        $query = Payment::with(['account', 'economicCode'])
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date_of_transaction', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date_of_transaction', '<=', $request->date_to));

        $payments = $query->orderBy('date_of_transaction')->get();
        $total = Money::normalize($payments->sum('amount'));

        return compact('payments', 'total');
    }

    protected function budgetReport(Request $request, ?FiscalYear $fiscalYear): array
    {
        $budgetService = app(BudgetService::class);

        $budgets = \App\Models\EconomicCodeBudget::with(['economicCode'])
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->orderBy('created_at')
            ->get()
            ->map(function ($budget) use ($budgetService, $fiscalYear) {
                return [
                    'budget' => $budget,
                    'revised' => $budgetService->revisedBudget($budget),
                    'paid' => $budgetService->paidPayments($budget->economicCode, $fiscalYear),
                    'available' => $budgetService->availableBudget($budget->economicCode, $fiscalYear),
                ];
            });

        return compact('budgets');
    }

    protected function virementReport(Request $request, ?FiscalYear $fiscalYear): array
    {
        $virements = Virement::with(['fromEconomicCode', 'toEconomicCode'])
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->orderBy('date')
            ->get();

        return compact('virements');
    }

    protected function accountStatement(Request $request, ?FiscalYear $fiscalYear): array
    {
        $accounts = Account::orderBy('account_name')->get();

        return compact('accounts', 'fiscalYear');
    }

    protected function exportExcel(string $report, array $data): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->streamDownload(function () use ($report, $data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Report', strtoupper(str_replace('_', ' ', $report))]);
            fputcsv($out, []);

            foreach ($data as $key => $value) {
                if (is_iterable($value)) {
                    foreach ($value as $row) {
                        fputcsv($out, array_values(is_array($row) ? $row : $row->toArray()));
                    }
                }
            }

            fclose($out);
        }, $report.'.csv');
    }
}
