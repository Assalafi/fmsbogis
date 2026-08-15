<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\PaymentService;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        $query = Payment::with(['account', 'economicCode', 'fiscalYear', 'creator'])
            ->where('fiscal_year_id', $fiscalYearId);

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('account_type')) {
            $query->whereHas('account', fn ($q) => $q->where('account_type', $request->account_type));
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

        $payments = $query->orderBy('date_of_transaction', 'desc')->paginate(20)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'fiscalYears' => FiscalYear::orderBy('start_date', 'desc')->get(),
            'accounts' => Account::orderBy('account_name')->get(),
            'expenseCodes' => EconomicCode::expense()->orderBy('code')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $fiscalYearId = $request->filled('fiscal_year_id') ? $request->fiscal_year_id : ActiveFiscalYear::id();

        return view('payments.create', [
            'accounts' => Account::active()->orderBy('account_name')->get(),
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
            'dept_voucher_number' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $economicCode = EconomicCode::findOrFail($data['economic_code_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        $error = app(PaymentService::class)->validate($account, $economicCode, $fiscalYear, $data['amount']);

        if ($error) {
            return back()->withInput()->withErrors(['amount' => $error]);
        }

        $payment = Payment::create($data + [
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        app(AuditService::class)->log('Payment Created', $payment, null, $data);

        return redirect()->route('payments.show', $payment)->with($this->toast('Payment saved successfully.'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver', 'payer', 'cashbookEntry']);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        abort_unless(in_array($payment->status, ['draft', 'pending']), 403, 'Only draft or pending payments can be edited.');

        return view('payments.edit', [
            'payment' => $payment,
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'fiscalYears' => FiscalYear::open()->orderBy('start_date')->get(),
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        abort_unless(in_array($payment->status, ['draft', 'pending']), 403, 'Only draft or pending payments can be edited.');

        $data = $request->validate([
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'economic_code_id' => ['required', 'uuid', 'exists:economic_codes,id'],
            'fiscal_year_id' => ['required', 'uuid', 'exists:fiscal_years,id'],
            'date_of_transaction' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'treasury_receipt_voucher_number' => ['required', 'string', 'max:255'],
            'from_whom_received_to_whom_paid' => ['nullable', 'string', 'max:255'],
            'bank_credit_slip_cheque_mandate_number' => ['nullable', 'string', 'max:255'],
            'dept_voucher_number' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:bank,cash'],
            'details' => ['required', 'string'],
        ]);

        $account = Account::findOrFail($data['account_id']);
        $economicCode = EconomicCode::findOrFail($data['economic_code_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        $error = app(PaymentService::class)->validate($account, $economicCode, $fiscalYear, $data['amount']);
        if ($error) {
            return back()->withInput()->withErrors(['amount' => $error]);
        }

        $old = $payment->only(array_keys($data));
        $payment->update($data);

        app(AuditService::class)->log('Payment Updated', $payment, $old, $data);

        return redirect()->route('payments.show', $payment)->with($this->toast('Payment updated.'));
    }

    public function approve(Payment $payment)
    {
        try {
            app(PaymentService::class)->approve($payment);
        } catch (\DomainException $e) {
            return back()->with($this->toast($e->getMessage(), 'danger'));
        }

        return back()->with($this->toast('Payment approved. Budget reserved.'));
    }

    public function reject(Request $request, Payment $payment)
    {
        app(PaymentService::class)->reject($payment, (string) $request->input('comment', ''));

        return back()->with($this->toast('Payment rejected.', 'danger'));
    }

    public function markPaid(Payment $payment)
    {
        try {
            app(PaymentService::class)->markPaid($payment);
        } catch (\DomainException $e) {
            return back()->with($this->toast($e->getMessage(), 'danger'));
        }

        return back()->with($this->toast('Payment marked as paid. Cashbook entry generated.'));
    }

    public function reverse(Payment $payment)
    {
        app(PaymentService::class)->reverse($payment);

        return back()->with($this->toast('Payment reversed. Reversing cashbook entry created.', 'danger'));
    }

    public function approval()
    {
        $payments = Payment::with(['account', 'economicCode', 'fiscalYear', 'creator'])
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->paginate(20);

        return view('payments.approval', compact('payments'));
    }

    public function print(Payment $payment)
    {
        $pdf = Pdf::loadView('payments.print', [
            'payment' => $payment->load(['account', 'economicCode', 'fiscalYear', 'creator', 'approver', 'payer']),
            'amountInWords' => Money::inWords($payment->amount),
        ])->setPaper('a4');

        return $pdf->stream('payment-voucher-'.$payment->treasury_receipt_voucher_number.'.pdf');
    }
}
