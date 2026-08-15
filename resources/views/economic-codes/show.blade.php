@extends('layouts.app')

@section('title', $economicCode->code.' — '.$economicCode->name)

@section('content')
    <x-page-header title="{{ $economicCode->code }} — {{ $economicCode->name }}" :breadcrumbs="['Economic Codes' => route('economic-codes.index'), $economicCode->code => null]">
        @can('economic_codes.update')
        <a href="{{ route('economic-codes.edit', $economicCode) }}" class="btn btn-primary">Edit</a>
        @endcan
    </x-page-header>

    @if($economicCode->isExpense())
        <div class="row">
            <x-stat-card label="ORIGINAL BUDGET" value="₦{{ number_format((float) ($budget?->original_budget ?? 0), 2) }}" icon="account_balance_wallet" color="secondary" />
            <x-stat-card label="REVISED BUDGET" value="₦{{ number_format((float) ($budget ? $budgetService->revisedBudget($budget) : 0), 2) }}" icon="tune" color="primary" />
            <x-stat-card label="PAID PAYMENTS" value="₦{{ number_format((float) ($fiscalYear ? $budgetService->paidPayments($economicCode, $fiscalYear) : 0), 2) }}" icon="north_east" color="danger" />
            <x-stat-card label="AVAILABLE BUDGET" value="₦{{ number_format((float) ($fiscalYear ? $budgetService->availableBudget($economicCode, $fiscalYear) : 0), 2) }}" icon="savings" color="success" />
        </div>
    @else
        <div class="row">
            <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $economicCode->receipts()->where('status', 'posted')->sum('amount'), 2) }}" icon="south_west" color="success" />
            <x-stat-card label="RECEIPT COUNT" value="{{ $economicCode->receipts()->where('status', 'posted')->count() }}" icon="receipt_long" color="primary" />
            <x-stat-card label="TYPE" value="{{ ucfirst($economicCode->type) }}" icon="tag" color="info" />
            <x-stat-card label="STATUS" value="{{ ucfirst($economicCode->status) }}" icon="check_circle" color="{{ $economicCode->isActive() ? 'success' : 'secondary' }}" />
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Economic Code Information</h4>
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr><th class="ps-0 fs-14 text-secondary" style="width: 40%;">Code</th><td class="pe-0 fw-medium">{{ $economicCode->code }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Name</th><td class="pe-0">{{ $economicCode->name }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Type</th><td class="pe-0">{{ ucfirst($economicCode->type) }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Account Type</th><td class="pe-0">{{ $economicCode->account_type ? ucfirst($economicCode->account_type) : '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Description</th><td class="pe-0">{{ $economicCode->description ?? '—' }}</td></tr>
                        <tr><th class="ps-0 fs-14 text-secondary">Status</th><td class="pe-0">@include('components.status-badge', ['status' => $economicCode->status])</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3">
                <h4 class="mb-4">Recent Transactions</h4>
                <ul class="list-unstyled ps-0 mb-0">
                    @forelse($economicCode->receipts()->latest()->take(5)->get()->merge($economicCode->payments()->latest()->take(5)->get())->sortByDesc('created_at') as $tx)
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <span class="fw-medium d-block">{{ $tx instanceof \App\Models\Receipt ? 'Receipt' : 'Payment' }}</span>
                                <span class="fs-13 text-secondary">{{ $tx->date_of_transaction->format('d M Y') }} — {{ $tx->treasury_receipt_voucher_number }}</span>
                            </div>
                            <span class="fw-medium text-{{ $tx instanceof \App\Models\Receipt ? 'success' : 'danger' }}">
                                {{ $tx instanceof \App\Models\Receipt ? '+' : '−' }}₦{{ number_format((float) $tx->amount, 2) }}
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
