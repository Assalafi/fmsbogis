<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Receipt;
use App\Services\AuditService;
use App\Services\ReceiptService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        $query = Receipt::with(['account', 'economicCode', 'fiscalYear', 'creator'])
            ->where('fiscal_year_id', $fiscalYearId);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('economic_code_id')) {
            $query->where('economic_code_id', $request->economic_code_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_of_transaction', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_of_transaction', '<=', $request->date_to);
        }

        $receipts = $query->orderBy('date_of_transaction', 'desc')->paginate(20)->withQueryString();

        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $accounts = Account::orderBy('account_name')->get();
        $revenueCodes = EconomicCode::revenue()->orderBy('code')->get();

        $receiptService = app(ReceiptService::class);
        $fiscalYear = FiscalYear::find($fiscalYearId);
        $summary = $fiscalYear ? $receiptService->totalsForPeriod($fiscalYear) : null;

        return view('receipts.index', compact('receipts', 'fiscalYears', 'accounts', 'revenueCodes', 'summary'));
    }

    public function create(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        return view('receipts.create', [
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'revenueCodes' => EconomicCode::revenue()->active()->orderBy('code')->get(),
            'fiscalYears' => FiscalYear::open()->orderBy('start_date')->get(),
            'selectedFiscalYearId' => $fiscalYearId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'date_of_transaction' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'treasury_receipt_voucher_number' => ['required', 'string', 'max:255'],
            'from_whom_received_to_whom_paid' => ['nullable', 'string', 'max:255'],
            'bank_credit_slip_cheque_mandate_number' => ['nullable', 'string', 'max:255'],
            'expenditure_credits' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $economicCode = EconomicCode::findOrFail($data['economic_code_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        $error = app(ReceiptService::class)->validate($economicCode, $account);
        if ($error) {
            return back()->withInput()->withErrors(['economic_code_id' => $error]);
        }

        if (! $fiscalYear->isOpen()) {
            return back()->withInput()->withErrors(['fiscal_year_id' => 'The selected Fiscal Year is closed.']);
        }

        $receipt = DB::transaction(function () use ($data) {
            $receipt = Receipt::create($data + [
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            app(AuditService::class)->log('Receipt Created', $receipt, null, $data);

            return $receipt;
        });

        return redirect()->route('receipts.show', $receipt)->with($this->toast('Receipt saved successfully.'));
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver', 'poster', 'cashbookEntry']);

        return view('receipts.show', compact('receipt'));
    }

    public function edit(Receipt $receipt)
    {
        abort_unless(in_array($receipt->status, ['draft', 'pending']), 403, 'Only draft or pending receipts can be edited.');

        return view('receipts.edit', [
            'receipt' => $receipt,
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'revenueCodes' => EconomicCode::revenue()->active()->orderBy('code')->get(),
            'fiscalYears' => FiscalYear::open()->orderBy('start_date')->get(),
        ]);
    }

    public function update(Request $request, Receipt $receipt)
    {
        abort_unless(in_array($receipt->status, ['draft', 'pending']), 403, 'Only draft or pending receipts can be edited.');

        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'date_of_transaction' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'treasury_receipt_voucher_number' => ['required', 'string', 'max:255'],
            'from_whom_received_to_whom_paid' => ['nullable', 'string', 'max:255'],
            'bank_credit_slip_cheque_mandate_number' => ['nullable', 'string', 'max:255'],
            'expenditure_credits' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $old = $receipt->only(array_keys($data));
        $receipt->update($data);

        app(AuditService::class)->log('Receipt Updated', $receipt, $old, $data);

        return redirect()->route('receipts.show', $receipt)->with($this->toast('Receipt updated.'));
    }

    public function approve(Receipt $receipt)
    {
        $receipt->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        app(AuditService::class)->log('Receipt Approved', $receipt, ['status' => 'pending'], ['status' => 'approved']);

        return back()->with($this->toast('Receipt approved.'));
    }

    public function post(Receipt $receipt)
    {
        app(ReceiptService::class)->post($receipt);

        return back()->with($this->toast('Receipt posted. Cashbook entry generated.'));
    }

    public function reverse(Receipt $receipt)
    {
        app(ReceiptService::class)->reverse($receipt);

        return back()->with($this->toast('Receipt reversed. Reversing cashbook entry created.', 'danger'));
    }

    public function print(Receipt $receipt)
    {
        $pdf = Pdf::loadView('receipts.print', [
            'receipt' => $receipt->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver']),
            'amountInWords' => Money::inWords($receipt->amount),
        ])->setPaper('a4');

        return $pdf->stream('receipt-'.$receipt->treasury_receipt_voucher_number.'.pdf');
    }
}
