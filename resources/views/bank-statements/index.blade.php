@extends('layouts.app')

@section('title', 'Bank Statements')

@section('content')
    <x-page-header title="Bank Statements" :breadcrumbs="['Bank Statements' => null]">
        @can('bank_statements.create')
        <a href="{{ route('bank-statements.create') }}" class="btn btn-primary">
            <i class="material-symbols-outlined align-middle fs-18">upload</i>
            Upload Bank Statement
        </a>
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('bank-statements.index') }}" class="row g-3 align-items-end">
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
                    @foreach(['draft', 'imported', 'manual', 'reconciled'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
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
                        <th>Period From</th>
                        <th>Period To</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Closing Balance</th>
                        <th>Lines</th>
                        <th>Status</th>
                        <th>Uploaded By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $statement)
                        <tr>
                            <td class="fw-medium">{{ $statement->account->account_name }}</td>
                            <td>{{ $statement->statement_from->format('d M Y') }}</td>
                            <td>{{ $statement->statement_to->format('d M Y') }}</td>
                            <td class="text-end">₦{{ number_format((float) $statement->opening_balance, 2) }}</td>
                            <td class="text-end">₦{{ number_format((float) $statement->closing_balance, 2) }}</td>
                            <td>{{ $statement->lines()->count() }}</td>
                            <td>@include('components.status-badge', ['status' => $statement->status])</td>
                            <td>{{ $statement->uploader?->name ?? '—' }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('bank-statements.show', $statement) }}" class="text-info" title="View"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('bank_reconciliation.create')
                                    @if(!$statement->reconciliations()->exists() && !in_array($statement->status, ['reconciled']))
                                    <a href="{{ route('reconciliations.create', ['account_id' => $statement->account_id]) }}" class="text-success" title="Reconcile"><i class="material-symbols-outlined fs-20">rule</i></a>
                                    @endif
                                    @endcan
                                    @can('bank_statements.create')
                                    @if($statement->status === 'draft')
                                    <form method="POST" action="{{ route('bank-statements.destroy', $statement) }}" onsubmit="return confirm('Delete this bank statement? This cannot be undone.');">
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
                            <td colspan="9" class="text-center text-secondary py-4">
                                No bank statements found.
                                @can('bank_statements.create')
                                <div class="mt-2">
                                    <a href="{{ route('bank-statements.create') }}" class="btn btn-sm btn-primary">+ Upload Bank Statement</a>
                                </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $statements->links() }}
        </div>
    </div>
@endsection
