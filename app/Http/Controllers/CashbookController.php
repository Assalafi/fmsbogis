<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CashbookEntry;
use App\Models\FiscalYear;
use App\Services\CashbookService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CashbookController extends Controller
{
    public function show(Request $request, Account $account)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $query = CashbookEntry::with(['economicCode'])
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

        $entries = $query->orderBy('date')->orderBy('created_at')->paginate(50)->withQueryString();

        $cashbookService = app(CashbookService::class);

        $summary = [
            'opening_balance' => $fiscalYear ? $account->opening_balance : $account->opening_balance,
            'total_receipts' => Money::normalize($query->clone()->sum('receipt_amount')),
            'total_payments' => Money::normalize($query->clone()->sum('payment_amount')),
            'closing_balance' => $cashbookService->closingBalance($account, $fiscalYear),
        ];

        return view('cashbook.show', compact('account', 'entries', 'summary', 'fiscalYear'));
    }

    public function print(Request $request, Account $account)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();
        $fiscalYear = FiscalYear::find($fiscalYearId);

        $entries = CashbookEntry::with(['economicCode'])
            ->where('account_id', $account->id)
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('date', '<=', $request->date_to))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $summary = [
            'opening_balance' => $account->opening_balance,
            'total_receipts' => Money::normalize($entries->sum('receipt_amount')),
            'total_payments' => Money::normalize($entries->sum('payment_amount')),
            'closing_balance' => app(CashbookService::class)->closingBalance($account, $fiscalYear),
        ];

        $pdf = Pdf::loadView('cashbook.print', compact('account', 'entries', 'summary', 'fiscalYear'))->setPaper('a4', 'landscape');

        return $pdf->stream('cashbook-'.$account->account_name.'.pdf');
    }
}
