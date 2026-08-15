@extends('layouts.app')

@section('title', 'Payment Approval')

@section('content')
    <x-page-header title="Payment Approval" :breadcrumbs="['Payments' => route('payments.index'), 'Approval' => null]" />

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Voucher No.</th>
                        <th>Date</th>
                        <th>Payee</th>
                        <th>Account</th>
                        <th>Economic Code</th>
                        <th class="text-end">Amount</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="fw-medium">{{ $payment->treasury_receipt_voucher_number }}</td>
                            <td>{{ $payment->date_of_transaction->format('d M Y') }}</td>
                            <td>{{ $payment->from_whom_received_to_whom_paid ?? '—' }}</td>
                            <td>{{ $payment->account->account_name }}</td>
                            <td>{{ $payment->economicCode->code }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->creator?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-info text-white">
                                        <i class="material-symbols-outlined align-middle fs-18">visibility</i> View &amp; Approve
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No pending payments.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
