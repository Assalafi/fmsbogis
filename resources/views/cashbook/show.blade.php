@extends('layouts.app')

@section('title', 'Cashbook — '.$account->account_name)

@section('content')
    <x-page-header title="Cashbook — {{ $account->account_name }}" :breadcrumbs="['Accounts' => route('accounts.index'), $account->account_name => null]">
        <a href="{{ route('cashbook.print', $account) }}?{{ http_build_query(request()->only(['fiscal_year_id', 'date_from', 'date_to'])) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">print</i>
            Print Cashbook
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row">
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Bank Name</span><strong>{{ $account->bank_name }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Account Number</span><strong>{{ $account->account_number }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Account Type</span><strong>{{ ucfirst($account->account_type) }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Fiscal Year</span><strong>FY {{ $fiscalYear?->name ?? 'All' }}</strong></div>
        </div>
    </div>

    <div class="row">
        <x-stat-card label="OPENING BALANCE" value="₦{{ number_format((float) $summary['opening_balance'], 2) }}" icon="play_circle" color="secondary" />
        <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $summary['total_receipts'], 2) }}" icon="south_west" color="success" />
        <x-stat-card label="TOTAL PAYMENTS" value="₦{{ number_format((float) $summary['total_payments'], 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="CLOSING BALANCE" value="₦{{ number_format((float) $summary['closing_balance'], 2) }}" icon="account_balance" color="primary" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('cashbook.show', $account) }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', $fiscalYear?->id) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Type</label>
                <select name="transaction_type" class="form-select">
                    <option value="">All</option>
                    <option value="receipt" {{ request('transaction_type') === 'receipt' ? 'selected' : '' }}>Receipt</option>
                    <option value="payment" {{ request('transaction_type') === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="opening_balance" {{ request('transaction_type') === 'opening_balance' ? 'selected' : '' }}>Opening Balance</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Economic Code</th>
                        <th>Description</th>
                        <th class="text-end">Receipt</th>
                        <th class="text-end">Payment</th>
                        <th class="text-end">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->date->format('d M Y') }}</td>
                            <td class="fw-medium">{{ $entry->reference ?? '—' }}</td>
                            <td>{{ $entry->economicCode?->code ?? '—' }}</td>
                            <td>
                                {{ \Illuminate\Support\Str::limit($entry->details, 60) }}
                                <span class="badge bg-secondary ms-1">{{ str_replace('_', ' ', ucfirst($entry->transaction_type)) }}</span>
                            </td>
                            <td class="text-end text-success">{{ $entry->receipt_amount > 0 ? '₦'.number_format((float) $entry->receipt_amount, 2) : '—' }}</td>
                            <td class="text-end text-danger">{{ $entry->payment_amount > 0 ? '₦'.number_format((float) $entry->payment_amount, 2) : '—' }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $entry->running_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No cashbook entries found for this account.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $entries->links() }}
        </div>
    </div>
@endsection
