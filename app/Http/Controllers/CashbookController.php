<?php

namespace App\Http\Controllers;

use App\Exports\CashbookExport;
use App\Models\Account;
use App\Models\CashbookEntry;
use App\Models\FiscalYear;
use App\Models\Setting;
use App\Support\ActiveFiscalYear;
use App\Support\CashbookReport;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CashbookController extends Controller
{
    public function show(Request $request, Account $account)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $query = CashbookEntry::with(['economicCode', 'sourceReceipt', 'sourcePayment'])
            ->where('account_id', $account->id);

        if ($fiscalYear) {
            $query->where('fiscal_year_id', $fiscalYear->id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $totalReceipts = Money::normalize($query->clone()->sum('receipt_amount'));
        $totalPayments = Money::normalize($query->clone()->sum('payment_amount'));
        $entries = $query->orderBy('date')->orderBy('created_at')->paginate(50)->withQueryString();

        $summary = [
            'opening_balance' => $account->opening_balance,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'closing_balance' => Money::add($account->opening_balance, $totalReceipts, Money::sub(0, $totalPayments)),
        ];

        $cashbookRows = CashbookReport::rows($entries->getCollection());
        $periodLabel = $this->periodLabel($request, $fiscalYear);
        $organizationName = Setting::get('organization_name', 'Borno State Geographic Information Service');
        $mdaCode = 'BOGIS';

        return view('cashbook.show', compact(
            'account',
            'entries',
            'summary',
            'fiscalYear',
            'cashbookRows',
            'periodLabel',
            'organizationName',
            'mdaCode',
        ));
    }

    public function print(Request $request, Account $account)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $entries = CashbookEntry::with(['economicCode', 'sourceReceipt', 'sourcePayment'])
            ->where('account_id', $account->id)
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->transaction_type))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $totalReceipts = Money::normalize($entries->sum('receipt_amount'));
        $totalPayments = Money::normalize($entries->sum('payment_amount'));
        $summary = [
            'opening_balance' => $account->opening_balance,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'closing_balance' => Money::add($account->opening_balance, $totalReceipts, Money::sub(0, $totalPayments)),
        ];

        $cashbookRows = CashbookReport::rows($entries);
        $periodLabel = $this->periodLabel($request, $fiscalYear);
        $organizationName = Setting::get('organization_name', 'Borno State Geographic Information Service');
        $mdaCode = 'BOGIS';

        $pdf = Pdf::loadView('cashbook.print', compact(
            'account',
            'entries',
            'summary',
            'fiscalYear',
            'cashbookRows',
            'periodLabel',
            'organizationName',
            'mdaCode',
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('cashbook-'.$account->account_name.'.pdf');
    }

    public function excel(Request $request, Account $account)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $entries = CashbookEntry::with(['economicCode', 'sourceReceipt', 'sourcePayment'])
            ->where('account_id', $account->id)
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->filled('transaction_type'), fn ($q) => $q->where('transaction_type', $request->transaction_type))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $totalReceipts = Money::normalize($entries->sum('receipt_amount'));
        $totalPayments = Money::normalize($entries->sum('payment_amount'));
        $summary = [
            'opening_balance' => $account->opening_balance,
            'total_receipts' => $totalReceipts,
            'total_payments' => $totalPayments,
            'closing_balance' => Money::add($account->opening_balance, $totalReceipts, Money::sub(0, $totalPayments)),
        ];
        $cashbookRows = CashbookReport::rows($entries);
        $periodLabel = $this->periodLabel($request, $fiscalYear);
        $organizationName = Setting::get('organization_name', 'Borno State Geographic Information Service');

        return Excel::download(
            new CashbookExport($account, $cashbookRows, $summary, $periodLabel, $organizationName),
            'cashbook-'.str_replace([' ', '/'], '-', $account->account_name).'.xlsx'
        );
    }

    private function periodLabel(Request $request, ?FiscalYear $fiscalYear): string
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        if ($from && $to && $from->isSameMonth($to)) {
            return $from->format('F Y');
        }

        if ($from && $to) {
            return $from->format('d M Y').' - '.$to->format('d M Y');
        }

        if ($from) {
            return 'From '.$from->format('d M Y');
        }

        if ($to) {
            return 'To '.$to->format('d M Y');
        }

        return $fiscalYear ? 'FY '.$fiscalYear->name : now()->format('F Y');
    }
}
