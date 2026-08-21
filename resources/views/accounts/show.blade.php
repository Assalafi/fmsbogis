@extends('layouts.app')

@section('title', $account->account_name)

@section('content')
    <x-page-header title="{{ $account->account_name }}" :breadcrumbs="['Accounts' => route('accounts.index'), $account->account_name => null]">
        <a href="{{ route('cashbook.show', $account) }}" class="btn btn-success">Cashbook</a>
        @can('accounts.update')
        <a href="{{ route('accounts.edit', $account) }}" class="btn btn-primary">Edit</a>
        @endcan
    </x-page-header>

    <div class="row">
        <x-stat-card label="OPENING BALANCE" value="₦{{ number_format((float) $account->opening_balance, 2) }}" icon="play_circle" color="secondary" />
        <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $summary['total_receipts'], 2) }}" icon="south_west" color="success" />
        <x-stat-card label="TOTAL PAYMENTS" value="₦{{ number_format((float) $summary['total_payments'], 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="CASHBOOK BALANCE" value="₦{{ number_format((float) $summary['cashbook_balance'], 2) }}" icon="account_balance" color="primary" />
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Account Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary" style="width: 45%;">Account Name</th><td class="pe-0 fw-medium">{{ $account->account_name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Bank Name</th><td class="pe-0">{{ $account->bank_name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account Number</th><td class="pe-0">{{ $account->account_number }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account Type</th><td class="pe-0"><span class="badge bg-{{ \App\Support\AccountTypes::badgeColor($account->account_type) }}">{{ ucfirst($account->account_type) }}</span></td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $account->status])</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Created At</th><td class="pe-0">{{ $account->created_at->format('d M Y H:i') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Recent Activity</h4>
                <ul class="list-unstyled ps-0 mb-0">
                    @forelse($account->cashbookEntries()->latest()->take(8)->get() as $entry)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="fw-medium d-block">{{ $entry->transaction_type === 'receipt' ? 'Receipt' : ($entry->transaction_type === 'payment' ? 'Payment' : ucfirst(str_replace('_', ' ', $entry->transaction_type))) }}</span>
                                <span class="fs-13 text-secondary">{{ $entry->date->format('d M Y') }} — {{ $entry->reference }}</span>
                            </div>
                            <span class="fw-medium text-{{ $entry->receipt_amount > 0 ? 'success' : 'danger' }}">
                                {{ $entry->receipt_amount > 0 ? '+' : '−' }}₦{{ number_format((float) ($entry->receipt_amount > 0 ? $entry->receipt_amount : $entry->payment_amount), 2) }}
                            </span>
                        </li>
                    @empty
                        <li class="text-center text-secondary py-4">No transactions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
