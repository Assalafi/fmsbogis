@extends('layouts.app')

@section('title', 'Bank Statement — '.$statement->account->account_name)

@section('content')
    <x-page-header title="Bank Statement — {{ $statement->account->account_name }}" :breadcrumbs="['Bank Statements' => route('bank-statements.index'), $statement->statement_from->format('d M Y').' — '.$statement->statement_to->format('d M Y') => null]">
        @can('bank_reconciliation.create')
        @if(!$statement->reconciliations()->exists() && !in_array($statement->status, ['reconciled']))
        <a href="{{ route('reconciliations.create', ['account_id' => $statement->account_id]) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">rule</i>
            Start Reconciliation
        </a>
        @endif
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row">
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Account</span><strong>{{ $statement->account->account_name }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Period</span><strong>{{ $statement->statement_from->format('d M Y') }} — {{ $statement->statement_to->format('d M Y') }}</strong></div>
            <div class="col-md-2"><span class="fs-14 text-secondary d-block">Opening Balance</span><strong>₦{{ number_format((float) $statement->opening_balance, 2) }}</strong></div>
            <div class="col-md-2"><span class="fs-14 text-secondary d-block">Closing Balance</span><strong>₦{{ number_format((float) $statement->closing_balance, 2) }}</strong></div>
            <div class="col-md-2"><span class="fs-14 text-secondary d-block">Status</span>@include('components.status-badge', ['status' => $statement->status])</div>
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                        <th>Match Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement->lines as $line)
                        <tr>
                            <td>{{ $line->date_of_transaction->format('d M Y') }}</td>
                            <td>{{ $line->reference ?? '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($line->description, 60) }}</td>
                            <td class="text-end text-danger">{{ $line->debit > 0 ? '₦'.number_format((float) $line->debit, 2) : '—' }}</td>
                            <td class="text-end text-success">{{ $line->credit > 0 ? '₦'.number_format((float) $line->credit, 2) : '—' }}</td>
                            <td class="text-end">{{ $line->balance !== null ? '₦'.number_format((float) $line->balance, 2) : '—' }}</td>
                            <td>@include('components.status-badge', ['status' => $line->match_status])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No lines found for this statement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
