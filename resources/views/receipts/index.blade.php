@extends('layouts.app')

@section('title', 'Receipt Register')

@section('content')
    <x-page-header title="Receipt Register" :breadcrumbs="['Receipts' => null]">
        @can('receipts.create')
        <a href="{{ route('receipts.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">add</i>
            Create Receipt
        </a>
        @endcan
    </x-page-header>

    @if($summary)
    <div class="row">
        <x-stat-card label="TODAY'S RECEIPTS" value="₦{{ number_format((float) $summary['today'], 2) }}" icon="today" color="primary" />
        <x-stat-card label="THIS MONTH" value="₦{{ number_format((float) $summary['month'], 2) }}" icon="calendar_view_month" color="info" />
        <x-stat-card label="YEAR TO DATE" value="₦{{ number_format((float) $summary['year_to_date'], 2) }}" icon="trending_up" color="success" />
        <x-stat-card label="RECEIPT COUNT" value="{{ $summary['count'] }}" icon="receipt_long" color="secondary" />
    </div>
    @endif

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('receipts.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach($fiscalYears as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', \App\Support\ActiveFiscalYear::id()) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') === $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Economic Code</label>
                <select name="economic_code_id" class="form-select">
                    <option value="">All Codes</option>
                    @foreach($revenueCodes as $code)
                        <option value="{{ $code->id }}" {{ request('economic_code_id') === $code->id ? 'selected' : '' }}>{{ $code->code }} — {{ $code->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['draft', 'pending', 'approved', 'posted', 'reversed'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
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
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Treasury Receipt No.</th>
                        <th>Account</th>
                        <th>Economic Code</th>
                        <th>From Whom Received</th>
                        <th>Method</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td>{{ $receipt->date_of_transaction->format('d M Y') }}</td>
                            <td class="fw-medium">{{ $receipt->treasury_receipt_voucher_number }}</td>
                            <td>{{ $receipt->account->account_name }}</td>
                            <td>{{ $receipt->economicCode->code }}</td>
                            <td>{{ $receipt->from_whom_received_to_whom_paid ?? '—' }}</td>
                            <td><span class="badge bg-{{ $receipt->payment_method === 'bank' ? 'info' : 'warning' }}">{{ strtoupper($receipt->payment_method) }}</span></td>
                            <td class="text-end fw-medium text-success">₦{{ number_format((float) $receipt->amount, 2) }}</td>
                            <td>@include('components.status-badge', ['status' => $receipt->status])</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('receipts.create')
                                    @if(in_array($receipt->status, ['draft', 'pending']))
                                    <a href="{{ route('receipts.edit', $receipt) }}" class="text-primary" title="Edit"><i class="material-symbols-outlined fs-20">edit</i></a>
                                    @endif
                                    @endcan
                                    <a href="{{ route('receipts.print', $receipt) }}" class="text-secondary" title="Print" target="_blank"><i class="material-symbols-outlined fs-20">print</i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">
                                No receipts found.
                                @can('receipts.create')
                                <div class="mt-2">
                                    <a href="{{ route('receipts.create') }}" class="btn btn-sm btn-primary">+ Create Receipt</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $receipts->links() }}
        </div>
    </div>
@endsection
