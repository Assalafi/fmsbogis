@extends('layouts.app')

@section('title', 'New Reconciliation')

@section('content')
    <x-page-header title="New Bank Reconciliation" :breadcrumbs="['Reconciliations' => route('reconciliations.index'), 'New' => null]">
        <a href="{{ route('reconciliations.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="material-symbols-outlined">info</i>
        <div>
            Select a bank statement. Reconciliation uses its closing balance and the cashbook balance as at the statement end date; no bank transaction records are required.
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="GET" action="{{ route('reconciliations.create') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-6">
                <label class="form-label">Account</label>
                <select name="account_id" class="form-select" onchange="this.form.submit()">
                    @foreach($accounts as $item)
                        <option value="{{ $item->id }}" {{ $account?->id === $item->id ? 'selected' : '' }}>{{ $item->account_name }} — {{ $item->bank_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($account)
            <div class="border rounded-3 p-3 mb-4 bg-light">
                <strong>{{ $account->account_name }}</strong>
                <span class="text-secondary">· {{ $account->bank_name }} · {{ $account->account_number }} · {{ ucfirst($account->account_type) }}</span>
            </div>

            <form method="POST" action="{{ route('reconciliations.store') }}">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="row g-4 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">Bank Statement <span class="text-danger">*</span></label>
                        <select name="bank_statement_id" class="form-select" required>
                            <option value="">Select a statement</option>
                            @foreach($statements as $statement)
                                <option value="{{ $statement->id }}" {{ old('bank_statement_id', $selectedStatement?->id) === $statement->id ? 'selected' : '' }}>
                                    {{ $statement->statement_from->format('d M Y') }} — {{ $statement->statement_to->format('d M Y') }} ·
                                    Opening ₦{{ number_format((float) $statement->opening_balance, 2) }} · Closing ₦{{ number_format((float) $statement->closing_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @if($statements->isEmpty())
                            <div class="mt-2 text-danger">No available bank statement exists for this account.</div>
                            @can('bank_statements.create')
                                <a href="{{ route('bank-statements.create') }}" class="btn btn-sm btn-outline-primary mt-2">Create Bank Statement</a>
                            @endcan
                        @endif
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100" {{ $statements->isEmpty() ? 'disabled' : '' }}>
                            Create Reconciliation
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="text-secondary">Create an active account before starting a reconciliation.</div>
        @endif
    </div>
@endsection
