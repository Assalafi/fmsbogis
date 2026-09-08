@extends('layouts.app')

@section('title', 'Bank Statement — '.$statement->account->account_name)

@section('content')
    @php($reconciliation = $statement->reconciliations->first())
    @php($locked = $statement->isLocked())

    <x-page-header title="Bank Statement — {{ $statement->account->account_name }}" :breadcrumbs="['Bank Statements' => route('bank-statements.index'), $statement->statement_from->format('d M Y').' — '.$statement->statement_to->format('d M Y') => null]">
        @if($statement->hasAttachment())
            <a href="{{ route('bank-statements.download', $statement) }}" class="btn btn-outline-primary">
                <i class="material-symbols-outlined align-middle fs-18">download</i>
                Download Statement
            </a>
        @endif
        @can('bank_statements.create')
            @if(!$locked && !$reconciliation)
                <a href="{{ route('bank-statements.edit', $statement) }}" class="btn btn-outline-secondary">
                    <i class="material-symbols-outlined align-middle fs-18">edit</i>
                    Edit Statement
                </a>
            @endif
        @endcan
        @can('bank_reconciliation.create')
            @if($reconciliation)
                <a href="{{ route('reconciliations.show', $reconciliation) }}" class="btn btn-success">
                    <i class="material-symbols-outlined align-middle fs-18">rule</i>
                    {{ $reconciliation->status === 'approved' ? 'View Reconciliation' : 'Continue Reconciliation' }}
                </a>
            @else
                <a href="{{ route('reconciliations.create', ['account_id' => $statement->account_id, 'statement_id' => $statement->id]) }}" class="btn btn-success">
                    <i class="material-symbols-outlined align-middle fs-18">rule</i>
                    Start Reconciliation
                </a>
            @endif
        @endcan
    </x-page-header>

    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="material-symbols-outlined">info</i>
        <div>This statement records only the bank's opening and closing balances. No transaction-line entry or file processing is required.</div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <span class="fs-14 text-secondary d-block">Account</span>
                <strong>{{ $statement->account->account_name }}</strong>
                <small class="d-block text-secondary">{{ $statement->account->bank_name }} · {{ $statement->account->account_number }}</small>
            </div>
            <div class="col-md-3">
                <span class="fs-14 text-secondary d-block">Statement Period</span>
                <strong>{{ $statement->statement_from->format('d M Y') }} — {{ $statement->statement_to->format('d M Y') }}</strong>
            </div>
            <div class="col-md-3">
                <span class="fs-14 text-secondary d-block">Status</span>
                @include('components.status-badge', ['status' => $statement->status, 'label' => $statement->status === 'manual' ? 'Open' : null])
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 bg-white rounded-3 p-4 h-100">
                <span class="fs-14 text-secondary d-block mb-2">OPENING BALANCE</span>
                <h2 class="mb-0">₦{{ number_format((float) $statement->opening_balance, 2) }}</h2>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-0 bg-white rounded-3 p-4 h-100">
                <span class="fs-14 text-secondary d-block mb-2">CLOSING BALANCE</span>
                <h2 class="mb-0">₦{{ number_format((float) $statement->closing_balance, 2) }}</h2>
            </div>
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="row g-4">
            <div class="col-md-6">
                <span class="fs-14 text-secondary d-block">Supporting File</span>
                @if($statement->hasAttachment())
                    <a href="{{ route('bank-statements.download', $statement) }}" class="text-decoration-none">
                        <i class="material-symbols-outlined align-middle fs-18">attach_file</i>
                        {{ $statement->file_name }}
                    </a>
                    <small class="d-block text-secondary">Stored for reference only; its contents are not read by the system.</small>
                @else
                    <span>No file attached</span>
                @endif
            </div>
            <div class="col-md-6">
                <span class="fs-14 text-secondary d-block">Notes</span>
                <span>{{ $statement->notes ?: 'No notes provided.' }}</span>
            </div>
        </div>
    </div>
@endsection
