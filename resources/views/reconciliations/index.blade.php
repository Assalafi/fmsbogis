@extends('layouts.app')

@section('title', 'Bank Reconciliation')

@section('content')
    <x-page-header title="Bank Reconciliation" :breadcrumbs="['Reconciliations' => null]">
        @can('bank_reconciliation.create')
        <a href="{{ route('reconciliations.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">rule</i>
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
                    <option value="">All</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
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
                        <th>Account</th>
                        <th>Statement Period</th>
                        <th>Reconciliation Date</th>
                        <th class="text-end">Cashbook Balance</th>
                        <th class="text-end">Bank Balance</th>
                        <th class="text-end">Difference</th>
                        <th>Status</th>
                        <th>Prepared By</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $reconciliation)
                        <tr>
                            <td class="fw-medium">{{ $reconciliation->account->account_name }}</td>
                            <td>{{ $reconciliation->bankStatement->statement_from->format('d M Y') }} — {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</td>
                            <td>{{ $reconciliation->reconciliation_date->format('d M Y') }}</td>
                            <td class="text-end">₦{{ number_format((float) $reconciliation->cashbook_balance, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $reconciliation->bank_statement_balance, 2) }}</td>
                            <td class="text-end fw-medium {{ $reconciliation->difference == 0 ? 'text-success' : 'text-danger' }}">
                                ₦{{ number_format((float) $reconciliation->difference, 2) }}
                            </td>
                            <td>@include('components.status-badge', ['status' => $reconciliation->status])</td>
                            <td>{{ $reconciliation->preparer?->name ?? '—' }}</td>
                            <td>{{ $reconciliation->approver?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('reconciliations.show', $reconciliation) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    <a href="{{ route('reconciliations.print', $reconciliation) }}" class="text-secondary" title="Print" target="_blank"><i class="material-symbols-outlined fs-20">print</i></a>
                                    @can('bank_reconciliation.create')
                                    @if($reconciliation->status === 'draft')
                                    <form method="POST" action="{{ route('reconciliations.destroy', $reconciliation) }}" onsubmit="return confirm('Delete this draft reconciliation? This cannot be undone.');">
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
                        <tr>
                            <td colspan="10" class="text-center text-secondary py-4">
                                No reconciliations found.
                                @can('bank_reconciliation.create')
                                <div class="mt-2">
                                    <a href="{{ route('reconciliations.create') }}" class="btn btn-sm btn-primary">+ New Reconciliation</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $reconciliations->links() }}
        </div>
    </div>
@endsection
