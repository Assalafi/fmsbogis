@extends('layouts.app')

@section('title', 'Receipt '.$receipt->treasury_receipt_voucher_number)

@section('content')
    <x-page-header title="Receipt {{ $receipt->treasury_receipt_voucher_number }}" :breadcrumbs="['Receipts' => route('receipts.index'), $receipt->treasury_receipt_voucher_number => null]">
        <a href="{{ route('receipts.pdf', $receipt) }}" class="btn btn-danger" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">print</i>
            Print Cash Receipt
        </a>
        <a href="{{ route('receipts.print', $receipt) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">receipt_long</i>
            Print Statement
        </a>

        @can('receipts.approve')
            @if($receipt->status === 'pending')
            <form method="POST" action="{{ route('receipts.approve', $receipt) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Approve</button>
            </form>
            @endif
            @if(in_array($receipt->status, ['approved']))
            <form method="POST" action="{{ route('receipts.post', $receipt) }}"
                onsubmit="return confirm('Post this receipt? A Cashbook entry will be created.');">
                @csrf
                <button type="submit" class="btn btn-success">Post to Cashbook</button>
            </form>
            @endif
            @if($receipt->status === 'posted')
            <form method="POST" action="{{ route('receipts.reverse', $receipt) }}"
                onsubmit="return confirm('Reverse this posted receipt? A reversing Cashbook entry will be created.');">
                @csrf
                <button type="submit" class="btn btn-danger">Reverse</button>
            </form>
            @endif
        @endcan
    </x-page-header>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Receipt Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary" style="width: 40%;">Treasury Receipt No.</th><td class="pe-0 fw-medium">{{ $receipt->treasury_receipt_voucher_number }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Date</th><td class="pe-0">{{ $receipt->date_of_transaction->format('d M Y') }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account</th><td class="pe-0">{{ $receipt->account->account_name }} ({{ $receipt->account->bank_name }})</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Economic Code</th><td class="pe-0">{{ $receipt->economicCode->code }} — {{ $receipt->economicCode->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Fiscal Year</th><td class="pe-0">FY {{ $receipt->fiscalYear->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">From Whom Received</th><td class="pe-0">{{ $receipt->from_whom_received_to_whom_paid ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Bank Credit Slip</th><td class="pe-0">{{ $receipt->bank_credit_slip_cheque_mandate_number ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Expenditure Credits</th><td class="pe-0">{{ $receipt->expenditure_credits ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Payment Method</th><td class="pe-0"><span class="badge bg-{{ $receipt->payment_method === 'bank' ? 'info' : 'warning' }}">{{ strtoupper($receipt->payment_method) }}</span></td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Details</th><td class="pe-0">{{ $receipt->details }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $receipt->status])</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3 mb-4 text-center">
                <span class="fs-14 text-secondary d-block mb-1">Receipt Amount</span>
                <h2 class="text-success mb-0">₦{{ number_format((float) $receipt->amount, 2) }}</h2>
            </div>

            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">System Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary">Created By</th><td class="pe-0">{{ $receipt->creator?->name ?? '—' }} ({{ $receipt->created_at->format('d M Y H:i') }})</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Approved By</th><td class="pe-0">{{ $receipt->approver?->name ?? '—' }} @if($receipt->approved_at)<span class="fs-13 text-secondary">({{ $receipt->approved_at->format('d M Y H:i') }})</span>@endif</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Posted By</th><td class="pe-0">{{ $receipt->poster?->name ?? '—' }} @if($receipt->posted_at)<span class="fs-13 text-secondary">({{ $receipt->posted_at->format('d M Y H:i') }})</span>@endif</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Cashbook Entry</th>
                            <td class="pe-0">
                                @if($receipt->cashbookEntry)
                                    <a href="{{ route('cashbook.show', $receipt->account) }}" class="text-success text-decoration-none">
                                        Generated — {{ $receipt->cashbookEntry->date->format('d M Y') }}
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
@endsection
