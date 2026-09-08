@extends('layouts.app')

@section('title', 'Bank Reconciliation')

@section('content')
    <x-page-header title="Bank Reconciliation" :breadcrumbs="['Reconciliations' => null]">
        @can('bank_reconciliation.create')
            <a href="{{ route('reconciliations.create') }}" class="btn btn-primary">
                <i class="material-symbols-outlined align-middle fs-18">add</i>
                New Reconciliation
            </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('reconciliations.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fs-14">Account</label>
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') === $account->id ? 'selected' : '' }}>{{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label fs-14">Date From</label><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-2"><label class="form-label fs-14">Date To</label><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Account</th><th>Statement Period</th><th>Reconciliation Date</th><th class="text-end">Adjusted Cashbook</th><th class="text-end">Adjusted Bank</th><th class="text-end">Difference</th><th>Items</th><th>Status</th><th>Prepared / Approved</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($reconciliations as $reconciliation)
                        <tr>
                            <td><a href="{{ route('reconciliations.show', $reconciliation) }}" class="fw-medium text-decoration-none">{{ $reconciliation->account->account_name }}</a><small class="d-block text-secondary">{{ $reconciliation->account->bank_name }}</small></td>
                            <td>{{ $reconciliation->bankStatement->statement_from->format('d M Y') }} — {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</td>
                            <td>{{ $reconciliation->reconciliation_date->format('d M Y') }}</td>
                            <td class="text-end">₦{{ number_format((float) $reconciliation->adjusted_cashbook_balance, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $reconciliation->adjusted_bank_balance, 2) }}</td>
                            <td class="text-end fw-bold {{ \App\Support\Money::isZero($reconciliation->difference) ? 'text-success' : 'text-danger' }}">₦{{ number_format((float) $reconciliation->difference, 2) }}</td>
                            <td>{{ $reconciliation->items_count }}</td>
                            <td>@include('components.status-badge', ['status' => $reconciliation->status])</td>
                            <td>{{ $reconciliation->preparer?->name ?? '—' }}@if($reconciliation->approver)<small class="d-block text-success">Approved: {{ $reconciliation->approver->name }}</small>@endif</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('reconciliations.show', $reconciliation) }}" class="text-info" title="Open"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    <a href="{{ route('reconciliations.excel', $reconciliation) }}" class="text-success" title="Excel"><i class="material-symbols-outlined fs-20">download</i></a>
                                    <a href="{{ route('reconciliations.print', $reconciliation) }}" class="text-secondary" title="Print" target="_blank"><i class="material-symbols-outlined fs-20">print</i></a>
                                    @can('bank_reconciliation.create')
                                        @if($reconciliation->status === 'draft')
                                            <form method="POST" action="{{ route('reconciliations.destroy', $reconciliation) }}" onsubmit="return confirm('Delete this draft reconciliation? Statement transactions will remain available.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-danger border-0 bg-transparent p-0" title="Delete"><i class="material-symbols-outlined fs-20">delete</i></button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-secondary py-5">No bank reconciliations match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">{{ $reconciliations->links() }}</div>
    </div>
@endsection
