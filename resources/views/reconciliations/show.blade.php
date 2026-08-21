@extends('layouts.app')

@section('title', 'Reconciliation Workspace')

@section('content')
    <x-page-header title="Reconciliation — {{ $reconciliation->account->account_name }}" :breadcrumbs="['Reconciliations' => route('reconciliations.index'), $reconciliation->account->account_name => null]">
        <a href="{{ route('reconciliations.excel', $reconciliation) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ route('reconciliations.print', $reconciliation) }}" class="btn btn-secondary" target="_blank">Print Statement</a>
        @can('bank_reconciliation.approve')
        @if($reconciliation->status === 'draft')
        <form method="POST" action="{{ route('reconciliations.approve', $reconciliation) }}"
            onsubmit="return confirm('Approve this reconciliation? It will be locked permanently.');">
            @csrf
            <button type="submit" class="btn btn-success">Approve Reconciliation</button>
        </form>
        @endif
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row">
            <div class="col-md-4"><span class="fs-14 text-secondary d-block">Account</span><strong>{{ $reconciliation->account->account_name }}</strong></div>
            <div class="col-md-4"><span class="fs-14 text-secondary d-block">Statement</span><strong>{{ $reconciliation->bankStatement->statement_from->format('d M Y') }} — {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</strong></div>
            <div class="col-md-4"><span class="fs-14 text-secondary d-block">Status</span>@include('components.status-badge', ['status' => $reconciliation->status])</div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <h4 class="mb-4">Cashbook Transactions</h4>
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Date</th><th>Reference</th><th>Details</th><th class="text-end">Amount</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($cashbookEntries as $entry)
                                <tr class="{{ in_array($entry->id, $matchedEntryIds) ? 'table-success' : '' }}">
                                    <td>{{ $entry->date->format('d/m') }}</td>
                                    <td class="fw-medium">{{ $entry->reference }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($entry->details, 30) }}</td>
                                    <td class="text-end">
                                        {{ $entry->receipt_amount > 0 ? '+' : '−' }}₦{{ number_format((float) ($entry->receipt_amount > 0 ? $entry->receipt_amount : $entry->payment_amount), 2) }}
                                    </td>
                                    <td>
                                        @if(!in_array($entry->id, $matchedEntryIds) && $reconciliation->status === 'draft')
                                        <form method="POST" action="{{ route('reconciliations.outstanding', [$reconciliation, $entry]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Mark Outstanding / Uncredited">Outstanding</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-3">No cashbook entries in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card border-0 p-4 bg-white rounded-3 h-100">
                <h4 class="mb-4">Bank Statement Transactions</h4>
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Date</th><th>Reference</th><th>Description</th><th class="text-end">Amount</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($statementLines as $line)
                                <tr class="{{ in_array($line->id, $matchedLineIds) ? 'table-success' : '' }}">
                                    <td>{{ $line->date_of_transaction->format('d/m') }}</td>
                                    <td class="fw-medium">{{ $line->reference }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($line->description, 30) }}</td>
                                    <td class="text-end">
                                        {{ $line->isCredit() ? '+' : '−' }}₦{{ number_format((float) $line->amount(), 2) }}
                                    </td>
                                    <td>
                                        @if(!in_array($line->id, $matchedLineIds) && $reconciliation->status === 'draft')
                                        <form method="POST" action="{{ route('reconciliations.bank-only', [$reconciliation, $line->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Mark as Bank-Only (charge / direct credit)">Bank Only</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-3">No statement lines.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <h4 class="mb-4">Manual Matching</h4>
        @if($reconciliation->status === 'draft')
        <form method="POST" action="{{ route('reconciliations.match', $reconciliation) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="form-label">Cashbook Entry</label>
                <select name="cashbook_entry_id" class="form-select" required>
                    <option value="">Select entry</option>
                    @foreach($cashbookEntries->whereNotIn('id', $matchedEntryIds) as $entry)
                        <option value="{{ $entry->id }}">{{ $entry->date->format('d/m/Y') }} — {{ $entry->reference }} (₦{{ number_format((float) ($entry->receipt_amount > 0 ? $entry->receipt_amount : $entry->payment_amount), 2) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Bank Statement Line</label>
                <select name="bank_statement_line_id" class="form-select" required>
                    <option value="">Select line</option>
                    @foreach($statementLines->whereNotIn('id', $matchedLineIds) as $line)
                        <option value="{{ $line->id }}">{{ $line->date_of_transaction->format('d/m/Y') }} — {{ $line->reference ?? $line->description }} (₦{{ number_format((float) $line->amount(), 2) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Match</button>
            </div>
        </form>
        @endif
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <h4 class="mb-4">Reconciliation Items</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr><th>Type</th><th>Source</th><th class="text-end">Amount</th><th>Notes</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($reconciliation->items as $item)
                        <tr>
                            <td>@include('components.status-badge', ['status' => $item->item_type])</td>
                            <td>
                                @if($item->cashbookEntry)
                                    Cashbook: {{ $item->cashbookEntry->reference }}
                                @elseif($item->bankStatementLine)
                                    Bank: {{ $item->bankStatementLine->reference ?? $item->bankStatementLine->description }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">₦{{ number_format((float) $item->amount, 2) }}</td>
                            <td>{{ $item->notes }}</td>
                            <td>
                                @if($item->item_type === 'matched' && $reconciliation->status === 'draft')
                                <form method="POST" action="{{ route('reconciliations.unmatch', [$reconciliation, $item]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Unmatch</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-3">No items yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($reconciliation->status === 'draft')
    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <h4 class="mb-4">Bank Adjustment</h4>
        <form method="POST" action="{{ route('reconciliations.adjustments', $reconciliation) }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Amount (positive = add to cashbook)</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" placeholder="Bank charge, direct credit, etc.">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </form>
    </div>
    @endif

    <div class="card border-0 p-4 bg-white rounded-3">
        <h4 class="mb-4">Reconciliation Summary</h4>
        <div class="table-responsive">
            <table class="table table-borderless mb-0" style="max-width: 520px;">
                <tbody>
                    <tr><td class="ps-0 text-secondary">Cashbook Balance</td><td class="pe-0 text-end">₦{{ number_format((float) $reconciliation->cashbook_balance, 2) }}</td></tr>
                    <tr><td class="ps-0 text-secondary">Bank Statement Balance</td><td class="pe-0 text-end">₦{{ number_format((float) $reconciliation->bank_statement_balance, 2) }}</td></tr>
                    <tr><td class="ps-0 text-secondary">Adjusted Cashbook Balance</td><td class="pe-0 text-end">₦{{ number_format((float) $reconciliation->adjusted_cashbook_balance, 2) }}</td></tr>
                    <tr><td class="ps-0 text-secondary">Adjusted Bank Balance</td><td class="pe-0 text-end">₦{{ number_format((float) $reconciliation->adjusted_bank_balance, 2) }}</td></tr>
                    <tr class="fw-bold {{ $reconciliation->difference == 0 ? 'text-success' : 'text-danger' }}">
                        <td class="ps-0">Difference</td>
                        <td class="pe-0 text-end">₦{{ number_format((float) $reconciliation->difference, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
