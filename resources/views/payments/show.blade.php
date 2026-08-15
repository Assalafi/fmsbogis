@extends('layouts.app')

@section('title', 'Payment '.$payment->treasury_receipt_voucher_number)

@section('content')
    <x-page-header title="Payment {{ $payment->treasury_receipt_voucher_number }}" :breadcrumbs="['Payments' => route('payments.index'), $payment->treasury_receipt_voucher_number => null]">
        <a href="{{ route('payments.print', $payment) }}" class="btn btn-secondary" target="_blank">Print Voucher</a>

        @can('payments.approve')
            @if($payment->status === 'pending')
            <form method="POST" action="{{ route('payments.approve', $payment) }}"
                onsubmit="return confirm('Approve this payment? The budget will be reserved.');">
                @csrf
                <button type="submit" class="btn btn-primary">Approve</button>
            </form>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
            @endif
        @endcan
        @can('payments.mark_paid')
            @if($payment->status === 'approved')
            <form method="POST" action="{{ route('payments.mark-paid', $payment) }}"
                onsubmit="return confirm('Mark this payment as PAID? A Cashbook entry will be created.');">
                @csrf
                <button type="submit" class="btn btn-success">Mark as Paid</button>
            </form>
            @endif
            @if($payment->status === 'paid')
            <form method="POST" action="{{ route('payments.reverse', $payment) }}"
                onsubmit="return confirm('Reverse this paid payment? A reversing Cashbook entry will be created.');">
                @csrf
                <button type="submit" class="btn btn-danger">Reverse</button>
            </form>
            @endif
        @endcan
    </x-page-header>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Payment Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary" style="width: 40%;">Treasury Voucher No.</th><td class="pe-0 fw-medium">{{ $payment->treasury_receipt_voucher_number }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Date</th><td class="pe-0">{{ $payment->date_of_transaction->format('d M Y') }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account</th><td class="pe-0">{{ $payment->account->account_name }} ({{ ucfirst($payment->account->account_type) }})</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Economic Code</th><td class="pe-0">{{ $payment->economicCode->code }} — {{ $payment->economicCode->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Fiscal Year</th><td class="pe-0">FY {{ $payment->fiscalYear->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">To Whom Paid</th><td class="pe-0">{{ $payment->from_whom_received_to_whom_paid ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Cheque/Mandate No.</th><td class="pe-0">{{ $payment->bank_credit_slip_cheque_mandate_number ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Dept. Voucher No.</th><td class="pe-0">{{ $payment->dept_voucher_number ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Payment Method</th><td class="pe-0"><span class="badge bg-{{ $payment->payment_method === 'bank' ? 'info' : 'warning' }}">{{ strtoupper($payment->payment_method) }}</span></td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Details</th><td class="pe-0">{{ $payment->details }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $payment->status])</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3 mb-4 text-center">
                <span class="fs-14 text-secondary d-block mb-1">Payment Amount</span>
                <h2 class="text-danger mb-0">₦{{ number_format((float) $payment->amount, 2) }}</h2>
            </div>

            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">System Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary">Created By</th><td class="pe-0">{{ $payment->creator?->name ?? '—' }} ({{ $payment->created_at->format('d M Y H:i') }})</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Approved By</th><td class="pe-0">{{ $payment->approver?->name ?? '—' }} @if($payment->approved_at)<span class="fs-13 text-secondary">({{ $payment->approved_at->format('d M Y H:i') }})</span>@endif</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Paid By</th><td class="pe-0">{{ $payment->payer?->name ?? '—' }} @if($payment->paid_at)<span class="fs-13 text-secondary">({{ $payment->paid_at->format('d M Y H:i') }})</span>@endif</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Cashbook Entry</th>
                            <td class="pe-0">
                                @if($payment->cashbookEntry)
                                    <a href="{{ route('cashbook.show', $payment->account) }}" class="text-success text-decoration-none">
                                        Generated — {{ $payment->cashbookEntry->date->format('d M Y') }}
                                    </a>
                                @else
                                    <span class="text-secondary">Not yet generated</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($payment->status === 'pending' && auth()->user()->can('payments.approve'))
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('payments.reject', $payment) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Comment</label>
                    <textarea name="comment" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection
