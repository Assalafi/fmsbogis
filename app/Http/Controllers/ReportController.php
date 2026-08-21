<?php

namespace App\Http\Controllers;

use App\Exports\ArrayExport;
use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Virement;
use App\Services\BudgetService;
use App\Services\CashbookService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
                    'total' => $budgetService->totalBudget($budget),
                    'paid' => $budgetService->paidPayments($budget->economicCode, $fiscalYear),
                    'available' => $budgetService->availableBudget($budget->economicCode, $fiscalYear),
                ];
            });

        return compact('budgets');
    }

    protected function virementReport(Request $request, ?FiscalYear $fiscalYear): array
    {
        $virements = Virement::with(['fromEconomicCode', 'toEconomicCode', 'preparer'])
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->orderBy('date')
            ->get();

        return compact('virements');
    }

    protected function accountStatement(Request $request, ?FiscalYear $fiscalYear): array
    {
        $cashbookService = app(CashbookService::class);

        $accounts = Account::orderBy('account_name')->get()->map(function (Account $account) use ($fiscalYear, $cashbookService) {
            $receipts = Money::normalize($account->receipts()
                ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                ->where('status', 'posted')
                ->sum('amount'));

            $payments = Money::normalize($account->payments()
                ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
                ->where('status', 'paid')
                ->sum('amount'));

            return [
                'account' => $account,
                'opening' => $account->opening_balance,
                'receipts' => $receipts,
                'payments' => $payments,
                'closing' => $cashbookService->closingBalance($account, $fiscalYear),
            ];
        });

        $totalOpening = Money::normalize($accounts->sum(fn ($row) => (float) $row['opening']));
        $totalReceipts = Money::normalize($accounts->sum(fn ($row) => (float) $row['receipts']));
        $totalPayments = Money::normalize($accounts->sum(fn ($row) => (float) $row['payments']));
        $totalClosing = Money::normalize($accounts->sum(fn ($row) => (float) $row['closing']));

        return compact('accounts', 'fiscalYear', 'totalOpening', 'totalReceipts', 'totalPayments', 'totalClosing');
    }

    protected function exportExcel(string $report, array $data): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        [$headings, $rows] = $this->buildExcelRows($report, $data);

        $title = ucwords(str_replace('_', ' ', $report));

        return Excel::download(new ArrayExport($headings, $rows, $title), str_replace(' ', '-', $title).'.xlsx');
    }

    protected function buildExcelRows(string $report, array $data): array
    {
        switch ($report) {
            case 'receipt_register':
                $headings = ['Date', 'Reference', 'Account', 'Economic Code', 'Details', 'Amount (₦)'];
                $rows = $data['receipts']->map(fn (Receipt $r) => [
                    $r->date_of_transaction->format('d/m/Y'),
                    $r->treasury_receipt_voucher_number,
                    $r->account?->account_name ?? '—',
                    $r->economicCode?->code ?? '—',
                    $r->details,
                    (float) $r->amount,
                ])->values()->all();

                return [$headings, $rows];

            case 'payment_register':
                $headings = ['Date', 'Reference', 'Account', 'Economic Code', 'Details', 'Amount (₦)'];
                $rows = $data['payments']->map(fn (Payment $p) => [
                    $p->date_of_transaction->format('d/m/Y'),
                    $p->treasury_receipt_voucher_number,
                    $p->account?->account_name ?? '—',
                    $p->economicCode?->code ?? '—',
                    $p->details,
                    (float) $p->amount,
                ])->values()->all();

                return [$headings, $rows];

            case 'budget_report':
                $headings = ['Economic Code', 'Description', 'Original (₦)', 'Supplementary (₦)', 'Virement In (₦)', 'Virement Out (₦)', 'Total (₦)', 'Paid (₦)', 'Available (₦)', 'Status'];
                $rows = $data['budgets']->map(function ($row) {
                    $budget = $row['budget'];

                    return [
                        $budget->economicCode?->code,
                        $budget->economicCode?->name,
                        (float) $budget->original_budget,
                        (float) $budget->supplementary_budget,
                        (float) $budget->virement_in,
                        (float) $budget->virement_out,
                        (float) $row['total'],
                        (float) $row['paid'],
                        (float) $row['available'],
                        ucfirst($budget->status),
                    ];
                })->values()->all();

                return [$headings, $rows];

            case 'virement_report':
                $headings = ['Date', 'From Code', 'From Description', 'To Code', 'To Description', 'Amount (₦)', 'Status', 'Prepared By'];
                $rows = $data['virements']->map(fn (Virement $v) => [
                    $v->date->format('d/m/Y'),
                    $v->fromEconomicCode?->code,
                    $v->fromEconomicCode?->name,
                    $v->toEconomicCode?->code,
                    $v->toEconomicCode?->name,
                    (float) $v->amount,
                    ucfirst($v->status),
                    $v->preparer?->name ?? '—',
                ])->values()->all();

                return [$headings, $rows];

            case 'account_statement':
                $headings = ['Account', 'Type', 'Bank', 'Account Number', 'Opening (₦)', 'Receipts (₦)', 'Payments (₦)', 'Closing (₦)'];
                $rows = $data['accounts']->map(function ($row) {
                    $account = $row['account'];

                    return [
                        $account->account_name,
                        ucfirst($account->account_type),
                        $account->bank_name,
                        $account->account_number,
                        (float) $row['opening'],
                        (float) $row['receipts'],
                        (float) $row['payments'],
                        (float) $row['closing'],
                    ];
                })->values()->all();
                $rows[] = [
                    'TOTAL', '', '', '',
                    (float) $data['totalOpening'],
                    (float) $data['totalReceipts'],
                    (float) $data['totalPayments'],
                    (float) $data['totalClosing'],
                ];

                return [$headings, $rows];

            default:
                $rows = [];
                foreach ($data as $key => $value) {
                    if (is_iterable($value)) {
                        foreach ($value as $item) {
                            $rows[] = array_values(is_array($item) ? $item : $item->toArray());
                        }
                    }
                }

                return [['Data'], $rows];
        }
    }
}
