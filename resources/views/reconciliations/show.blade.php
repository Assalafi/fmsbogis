@extends('layouts.app')

@section('title', 'Reconciliation Workspace')

@section('content')
    <x-page-header title="Reconciliation — {{ $reconciliation->account->account_name }}" :breadcrumbs="['Reconciliations' => route('reconciliations.index'), $reconciliation->account->account_name => null]">
        <a href="{{ route('bank-statements.show', $reconciliation->bankStatement) }}" class="btn btn-outline-primary">
            <i class="material-symbols-outlined align-middle fs-18">account_balance</i> Bank Statement
        </a>
        <a href="{{ route('reconciliations.excel', $reconciliation) }}" class="btn btn-success"><i class="material-symbols-outlined align-middle fs-18">download</i> Excel</a>
        <a href="{{ route('reconciliations.print', $reconciliation) }}" class="btn btn-secondary" target="_blank"><i class="material-symbols-outlined align-middle fs-18">print</i> Print</a>
        @can('bank_reconciliation.approve')
            @if($reconciliation->status === 'draft')
                <form method="POST" action="{{ route('reconciliations.approve', $reconciliation) }}" onsubmit="return confirm('Approve and permanently lock this reconciliation?');">
                    @csrf
                    <button type="submit" class="btn btn-success" {{ $canApprove ? '' : 'disabled' }} title="{{ $canApprove ? 'Approve reconciliation' : 'The adjusted balances must agree before approval' }}">
                        <i class="material-symbols-outlined align-middle fs-18">verified</i> Approve
                    </button>
                </form>
            @endif
        @endcan
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row g-3">
            <div class="col-md-4"><span class="fs-14 text-secondary d-block">Account</span><strong>{{ $reconciliation->account->account_name }}</strong><small class="d-block text-secondary">{{ $reconciliation->account->bank_name }} · {{ $reconciliation->account->account_number }}</small></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Statement Period</span><strong>{{ $reconciliation->bankStatement->statement_from->format('d M Y') }} — {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</strong></div>
            <div class="col-md-2"><span class="fs-14 text-secondary d-block">Statement Opening</span><strong>₦{{ number_format((float) $reconciliation->bankStatement->opening_balance, 2) }}</strong></div>
            <div class="col-md-1"><span class="fs-14 text-secondary d-block">Status</span>@include('components.status-badge', ['status' => $reconciliation->status])</div>
            <div class="col-md-2"><span class="fs-14 text-secondary d-block">Prepared By</span><strong>{{ $reconciliation->preparer?->name ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="row">
        <x-stat-card label="CASHBOOK BALANCE" value="₦{{ number_format((float) $reconciliation->cashbook_balance, 2) }}" icon="menu_book" color="primary" />
        <x-stat-card label="STATEMENT CLOSING" value="₦{{ number_format((float) $reconciliation->bank_statement_balance, 2) }}" icon="account_balance" color="info" />
        <x-stat-card label="ADJUSTED CASHBOOK" value="₦{{ number_format((float) $reconciliation->adjusted_cashbook_balance, 2) }}" icon="calculate" color="success" />
        <x-stat-card label="DIFFERENCE" value="₦{{ number_format((float) $reconciliation->difference, 2) }}" icon="balance" :color="\App\Support\Money::isZero($reconciliation->difference) ? 'success' : 'danger'" />
    </div>

    @if($canApprove && $reconciliation->status === 'draft')
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="material-symbols-outlined">check_circle</i> The adjusted cashbook and bank balances agree. This reconciliation is ready for approval.</div>
    @elseif($reconciliation->status === 'draft')
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="material-symbols-outlined">pending_actions</i>
            <div>The balances differ by <strong>₦{{ number_format(abs((float) $reconciliation->difference), 2) }}</strong>. Add the applicable aggregate reconciling adjustments below—no bank transaction records are required.</div>
        </div>
    @endif

    @if($reconciliation->status === 'draft')
        <div class="card border-0 p-4 bg-white rounded-3 mb-4">
            <h4 class="mb-1">Add Reconciling Adjustment</h4>
            <p class="text-secondary fs-14">Choose the reason and enter the total amount from your reconciliation working paper.</p>
            <x-validation-errors />
            <form method="POST" action="{{ route('reconciliations.adjustments', $reconciliation) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="form-label">Adjustment Category</label>
                    <select name="adjustment_category" class="form-select" required>
                        <option value="">Select category</option>
                        <optgroup label="Add to cashbook">
                            <option value="credit_transfer">Credit transfer</option>
                            <option value="interest_received">Interest received</option>
                            <option value="stale_cheque_reversed">Stale cheque reversed</option>
                            <option value="outstanding_stale_revenue">Outstanding stale cheque / revenue</option>
                            <option value="items_in_bank_not_cashbook">Items in Bank Not Cashbook Amount</option>
                            <option value="other_cashbook_addition">Other cashbook addition</option>
                        </optgroup>
                        <optgroup label="Less from cashbook">
                            <option value="bank_charge">Bank charge</option>
                            <option value="debit_transfer">Debit transfer</option>
                            <option value="other_cashbook_deduction">Other cashbook deduction</option>
                        </optgroup>
                        <optgroup label="Adjust bank statement balance">
                            <option value="uncredited_lodgement">Add: Uncredited lodgement</option>
                            <option value="items_in_cashbook_not_bank">Add: Items in Cashbook Not Bank Amount</option>
                            <option value="unpresented_payment">Less: Unpresented cheque / payment</option>
                            <option value="other_bank_addition">Other bank balance addition</option>
                            <option value="other_bank_deduction">Other bank balance deduction</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount (₦)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Additional Details</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional reference or explanation">
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Add Adjustment</button></div>
            </form>
        </div>
    @endif

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Reconciling Adjustments</h4><span class="text-secondary">{{ $reconciliation->items->count() }} item(s)</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Category</th><th>Effect</th><th>Details</th><th class="text-end">Amount</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($reconciliation->items as $item)
                        <tr>
                            <td class="fw-medium">{{ $item->displayType() }}</td>
                            <td>{{ $item->effectLabel() }}</td>
                            <td>{{ $item->notes ?: '—' }}</td>
                            <td class="text-end">₦{{ number_format((float) $item->amount, 2) }}</td>
                            <td>
                                @if($reconciliation->status === 'draft')
                                    <form method="POST" action="{{ route('reconciliations.unmatch', [$reconciliation, $item]) }}" onsubmit="return confirm('Remove this adjustment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                @else
                                    <i class="material-symbols-outlined text-secondary fs-18">lock</i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No reconciling adjustments have been entered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <h4 class="mb-4">Reconciliation Summary</h4>
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="max-width:760px">
                <tbody>
                    <tr class="fw-bold"><td>Cashbook Balance as at {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</td><td class="text-end">₦{{ number_format((float) $reconciliation->cashbook_balance, 2) }}</td></tr>
                    <tr><td class="ps-4 text-success">Add: Credit transfers, interest and items in bank not cashbook</td><td class="text-end text-success">₦{{ number_format((float) $breakdown['cashbook_additions'], 2) }}</td></tr>
                    <tr><td class="ps-4 text-danger">Less: Bank charges, debit transfers and cashbook deductions</td><td class="text-end text-danger">₦{{ number_format((float) $breakdown['cashbook_deductions'], 2) }}</td></tr>
                    <tr class="table-primary fw-bold"><td>Adjusted Cashbook Balance</td><td class="text-end">₦{{ number_format((float) $reconciliation->adjusted_cashbook_balance, 2) }}</td></tr>
                    <tr><td colspan="2" class="border-0 py-2"></td></tr>
                    <tr class="fw-bold"><td>Bank Statement Closing Balance</td><td class="text-end">₦{{ number_format((float) $reconciliation->bank_statement_balance, 2) }}</td></tr>
                    <tr><td class="ps-4 text-success">Add: Uncredited lodgements and items in cashbook not bank</td><td class="text-end text-success">₦{{ number_format((float) $breakdown['bank_additions'], 2) }}</td></tr>
                    <tr><td class="ps-4 text-danger">Less: Unpresented payments and other bank deductions</td><td class="text-end text-danger">₦{{ number_format((float) $breakdown['bank_deductions'], 2) }}</td></tr>
                    <tr class="table-primary fw-bold"><td>Adjusted Bank Balance</td><td class="text-end">₦{{ number_format((float) $reconciliation->adjusted_bank_balance, 2) }}</td></tr>
                    <tr class="fw-bold fs-18 {{ \App\Support\Money::isZero($reconciliation->difference) ? 'table-success' : 'table-danger' }}"><td>Difference</td><td class="text-end">₦{{ number_format((float) $reconciliation->difference, 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
