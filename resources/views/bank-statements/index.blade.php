@extends('layouts.app')

@section('title', 'Bank Statements')

@section('content')
    <x-page-header title="Bank Statements" :breadcrumbs="['Bank Statements' => null]">
        @can('bank_statements.create')
            <a href="{{ route('bank-statements.create') }}" class="btn btn-primary">
                <i class="material-symbols-outlined align-middle fs-18">add</i>
                New Bank Statement
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
                    <option value="">All Statuses</option>
                    <option value="manual" {{ request('status') === 'manual' ? 'selected' : '' }}>Open</option>
                    <option value="reconciled" {{ request('status') === 'reconciled' ? 'selected' : '' }}>Reconciled</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Period From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Period To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Search</label>
                <input type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Account or file">
            </div>
            <div class="col-md-1">
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
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Closing Balance</th>
                        <th>Attachment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $statement)
                        <tr>
                            <td>
                                <a href="{{ route('bank-statements.show', $statement) }}" class="fw-medium text-decoration-none">{{ $statement->account->account_name }}</a>
                                <small class="d-block text-secondary">{{ $statement->account->bank_name }} · {{ $statement->account->account_number }}</small>
                            </td>
                            <td>{{ $statement->statement_from->format('d M Y') }} — {{ $statement->statement_to->format('d M Y') }}</td>
                            <td class="text-end">₦{{ number_format((float) $statement->opening_balance, 2) }}</td>
                            <td class="text-end fw-medium">₦{{ number_format((float) $statement->closing_balance, 2) }}</td>
                            <td>
                                @if($statement->hasAttachment())
                                    <a href="{{ route('bank-statements.download', $statement) }}" title="Download {{ $statement->file_name }}" class="text-decoration-none">
                                        <i class="material-symbols-outlined align-middle fs-18">attach_file</i> File
                                    </a>
                                @else
                                    <span class="text-secondary">None</span>
                                @endif
                            </td>
                            <td>
                                @if($statement->status === 'manual')
                                    <span class="badge bg-info bg-opacity-75 text-white">Open</span>
                                @else
                                    @include('components.status-badge', ['status' => $statement->status])
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ route('bank-statements.show', $statement) }}" class="text-info" title="Open statement"><i class="material-symbols-outlined fs-20">visibility</i></a>
                                    @can('bank_statements.create')
                                        @if($statement->status !== 'reconciled' && $statement->reconciliations_count === 0)
                                            <a href="{{ route('bank-statements.edit', $statement) }}" class="text-primary" title="Edit statement"><i class="material-symbols-outlined fs-20">edit</i></a>
                                        @endif
                                    @endcan
                                    @can('bank_reconciliation.create')
                                        @if($statement->reconciliations_count === 0 && $statement->status !== 'reconciled')
                                            <a href="{{ route('reconciliations.create', ['account_id' => $statement->account_id, 'statement_id' => $statement->id]) }}" class="text-success" title="Start reconciliation"><i class="material-symbols-outlined fs-20">rule</i></a>
                                        @endif
                                    @endcan
                                    @can('bank_statements.create')
                                        @if($statement->status !== 'reconciled' && $statement->reconciliations_count === 0)
                                            <form method="POST" action="{{ route('bank-statements.destroy', $statement) }}" onsubmit="return confirm('Delete this bank statement and all its transaction lines?');">
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
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="material-symbols-outlined d-block fs-36 mb-2">account_balance</i>
                                No bank statements match these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">{{ $statements->links() }}</div>
    </div>
@endsection
