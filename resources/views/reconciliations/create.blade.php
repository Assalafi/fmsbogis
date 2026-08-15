@extends('layouts.app')

@section('title', 'New Reconciliation')

@section('content')
    <x-page-header title="New Reconciliation" :breadcrumbs="['Reconciliations' => route('reconciliations.index'), 'New' => null]">
        <a href="{{ route('reconciliations.index') }}" class="btn btn-outline-secondary">
            <i class="material-symbols-outlined align-middle fs-18">arrow_back</i>
            Back
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3">
        <x-validation-errors />

        <form method="GET" action="{{ route('reconciliations.create') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-5">
                <label class="form-label">Select Account</label>
                <select name="account_id" class="form-select" onchange="this.form.submit()">
                    @foreach($accounts as $item)
                        <option value="{{ $item->id }}" {{ $account?->id === $item->id ? 'selected' : '' }}>{{ $item->account_name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($account)
            <div class="alert alert-info">
                <strong>{{ $account->account_name }}</strong> — {{ $account->bank_name }} ({{ $account->account_number }}) · {{ ucfirst($account->account_type) }}
            </div>

            <form method="POST" action="{{ route('reconciliations.store') }}">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label">Select Bank Statement <span class="text-danger">*</span></label>
                        <select name="bank_statement_id" class="form-select" required>
                            <option value="">Select Statement</option>
                            @foreach($statements as $statement)
                                <option value="{{ $statement->id }}">
                                    {{ $statement->statement_from->format('d M Y') }} — {{ $statement->statement_to->format('d M Y') }}
                                    (Closing: ₦{{ number_format((float) $statement->closing_balance, 2) }} · {{ $statement->lines()->count() }} lines)
                                </option>
                            @endforeach
                        </select>
                        @if($statements->isEmpty())
                            <small class="text-danger">No unreconciled bank statements exist for this account. Upload one first.</small>
                        @endif
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" {{ $statements->isEmpty() ? 'disabled' : '' }}>
                            Create &amp; Auto-Match
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
