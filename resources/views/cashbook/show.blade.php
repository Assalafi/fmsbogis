@extends('layouts.app')

@section('title', 'Cashbook — '.$account->account_name)

@push('styles')
    <style>
        .cashbook-scroll { overflow-x: auto; }
        .cashbook-format { min-width: 2200px; width: 100%; border-collapse: collapse; color: #000; font-family: Arial, sans-serif; font-size: 11px; }
        .cashbook-format th, .cashbook-format td { border: 1px solid #333; padding: 5px 6px; vertical-align: bottom; }
        .cashbook-format .document-title th { border-left: 0; border-right: 0; padding: 2px 6px; text-align: center; font-size: 16px; font-weight: 700; }
        .cashbook-format .spacer-row th { height: 16px; }
        .cashbook-format .mda-row th { height: 34px; font-weight: 700; }
        .cashbook-format .mda-value { text-align: left; }
        .cashbook-format .mda-code-label { text-align: right; }
        .cashbook-format .mda-code-value { text-align: center; }
        .cashbook-format .ledger-side th { height: 26px; text-align: left; font-weight: 700; }
        .cashbook-format .ledger-side .credit-label { text-align: right; }
        .cashbook-format .column-headings th { height: 70px; text-align: center; white-space: normal; font-weight: 700; }
        .cashbook-format tbody td { height: 28px; }
        .cashbook-format .amount { text-align: right; white-space: nowrap; }
        .cashbook-format .center { text-align: center; }
        .cashbook-format .ledger-total td { font-weight: 700; }
        .cashbook-summary { width: 430px; margin: 26px 0 0 24%; color: #000; font-family: Arial, sans-serif; font-size: 12px; }
        .cashbook-summary-title { margin-bottom: 8px; font-weight: 700; text-transform: uppercase; }
        .cashbook-summary table { width: 100%; border-collapse: collapse; }
        .cashbook-summary th, .cashbook-summary td { padding: 4px 6px; }
        .cashbook-summary th { text-align: left; font-weight: 400; }
        .cashbook-summary td { text-align: right; }
        .cashbook-summary .gross-heading th, .cashbook-summary .gross-heading td,
        .cashbook-summary .closing-row th, .cashbook-summary .closing-row td { font-weight: 700; }
    </style>
@endpush

@section('content')
    <x-page-header title="Cashbook — {{ $account->account_name }}" :breadcrumbs="['Accounts' => route('accounts.index'), $account->account_name => null]">
        <a href="{{ route('cashbook.excel', $account) }}?{{ http_build_query(request()->only(['fiscal_year_id', 'date_from', 'date_to', 'transaction_type'])) }}" class="btn btn-success">
            <i class="material-symbols-outlined align-middle fs-18">download</i>
            Excel
        </a>
        <a href="{{ route('cashbook.print', $account) }}?{{ http_build_query(request()->only(['fiscal_year_id', 'date_from', 'date_to', 'transaction_type'])) }}" class="btn btn-secondary" target="_blank">
            <i class="material-symbols-outlined align-middle fs-18">print</i>
            Print Cashbook
        </a>
    </x-page-header>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <div class="row">
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Bank Name</span><strong>{{ $account->bank_name }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Account Number</span><strong>{{ $account->account_number }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Account Type</span><strong>{{ ucfirst($account->account_type) }}</strong></div>
            <div class="col-md-3"><span class="fs-14 text-secondary d-block">Fiscal Year</span><strong>FY {{ $fiscalYear?->name ?? 'All' }}</strong></div>
        </div>
    </div>

    <div class="row">
        <x-stat-card label="OPENING BALANCE" value="₦{{ number_format((float) $summary['opening_balance'], 2) }}" icon="play_circle" color="secondary" />
        <x-stat-card label="TOTAL RECEIPTS" value="₦{{ number_format((float) $summary['total_receipts'], 2) }}" icon="south_west" color="success" />
        <x-stat-card label="TOTAL PAYMENTS" value="₦{{ number_format((float) $summary['total_payments'], 2) }}" icon="north_east" color="danger" />
        <x-stat-card label="CLOSING BALANCE" value="₦{{ number_format((float) $summary['closing_balance'], 2) }}" icon="account_balance" color="primary" />
    </div>

    <div class="card border-0 p-4 bg-white rounded-3 mb-4">
        <form method="GET" action="{{ route('cashbook.show', $account) }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-14">Fiscal Year</label>
                <select name="fiscal_year_id" class="form-select">
                    @foreach(\App\Models\FiscalYear::orderBy('start_date', 'desc')->get() as $fy)
                        <option value="{{ $fy->id }}" {{ request('fiscal_year_id', $fiscalYear?->id) === $fy->id ? 'selected' : '' }}>FY {{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-14">Type</label>
                <select name="transaction_type" class="form-select">
                    <option value="">All</option>
                    <option value="receipt" {{ request('transaction_type') === 'receipt' ? 'selected' : '' }}>Receipt</option>
                    <option value="payment" {{ request('transaction_type') === 'payment' ? 'selected' : '' }}>Payment</option>
                    <option value="opening_balance" {{ request('transaction_type') === 'opening_balance' ? 'selected' : '' }}>Opening Balance</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="card border-0 p-4 bg-white rounded-3">
        <div class="cashbook-scroll">
            <table class="cashbook-format">
                <colgroup>
                    <col style="width:4%"><col style="width:4%"><col style="width:3.5%"><col style="width:8.5%"><col style="width:4%">
                    <col style="width:4%"><col style="width:3.7%"><col style="width:5.6%"><col style="width:4.8%"><col style="width:5.4%">
                    <col style="width:5%"><col style="width:12%"><col style="width:4%"><col style="width:4.5%"><col style="width:4.3%">
                    <col style="width:4%"><col style="width:5.8%"><col style="width:5%"><col style="width:4.9%">
                </colgroup>
                <thead>
                    <tr class="document-title"><th colspan="19">BORNO STATE GOVERNMENT OF NIGERIA</th></tr>
                    <tr class="document-title"><th colspan="19">OFFICE OF THE ACCOUNTANT GENERAL</th></tr>
                    <tr class="document-title"><th colspan="19">Treasury Cash Book for the Month of {{ $periodLabel }}</th></tr>
                    <tr class="spacer-row"><th colspan="19"></th></tr>
                    <tr class="mda-row">
                        <th></th><th>NAME OF MDA:</th><th colspan="9" class="mda-value">{{ $organizationName }}</th>
                        <th colspan="2" class="mda-code-label">MDA CODE:</th><th colspan="5" class="mda-code-value">{{ $mdaCode }}</th><th></th>
                    </tr>
                    <tr class="ledger-side"><th colspan="10">DR.</th><th colspan="9" class="credit-label">CR.</th></tr>
                    <tr class="column-headings">
                        <th>DATE</th><th>TREASURY<br>RECEIPT No.</th><th>BANK CREDIT<br>SLIP No.</th><th>FROM WHOM RECEIVED</th>
                        <th>TREASURY<br>VOUCHER No.</th><th>EXPENDITURE<br>CREDITS</th><th>ECONOMIC<br>CODE</th><th>GROSS<br>₦</th><th>CASH<br>₦</th><th>BANK<br>₦</th>
                        <th>DATE</th><th>TO WHOM PAID</th><th>DEPT.<br>VOUCHER No.</th><th>TREASURY<br>VOUCHER No.</th>
                        <th>CHEQUE/MANDATE<br>No.</th><th>ECONOMIC<br>CODE</th><th>GROSS<br>₦</th><th>CASH<br>₦</th><th>BANK<br>₦</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashbookRows as $row)
                        @php($debit = $row['debit'])
                        @php($credit = $row['credit'])
                        <tr>
                            <td>{{ ($debit['date'] ?? null)?->format('d/M/Y') }}</td>
                            <td class="center">{{ $debit['treasury_receipt_number'] ?? '' }}</td>
                            <td class="center">{{ $debit['bank_credit_slip_number'] ?? '' }}</td>
                            <td>{{ $debit['from_whom_received'] ?? '' }}</td>
                            <td class="center">{{ $debit['treasury_voucher_number'] ?? '' }}</td>
                            <td>{{ $debit['expenditure_credits'] ?? '' }}</td>
                            <td class="center">{{ $debit['economic_code'] ?? '' }}</td>
                            <td class="amount">{{ isset($debit['gross']) ? number_format($debit['gross'], 2) : '' }}</td>
                            <td class="amount">{{ isset($debit['cash']) ? number_format($debit['cash'], 2) : '' }}</td>
                            <td class="amount">{{ isset($debit['bank']) ? number_format($debit['bank'], 2) : '' }}</td>
                            <td>{{ ($credit['date'] ?? null)?->format('d/M/Y') }}</td>
                            <td>{{ $credit['to_whom_paid'] ?? '' }}</td>
                            <td class="center">{{ $credit['dept_voucher_number'] ?? '' }}</td>
                            <td class="center">{{ $credit['treasury_voucher_number'] ?? '' }}</td>
                            <td class="center">{{ $credit['cheque_mandate_number'] ?? '' }}</td>
                            <td class="center">{{ $credit['economic_code'] ?? '' }}</td>
                            <td class="amount">{{ isset($credit['gross']) ? number_format($credit['gross'], 2) : '' }}</td>
                            <td class="amount">{{ isset($credit['cash']) ? number_format($credit['cash'], 2) : '' }}</td>
                            <td class="amount">{{ isset($credit['bank']) ? number_format($credit['bank'], 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="19" class="text-center text-secondary py-4">No cashbook entries found for this account.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="ledger-total">
                        <td colspan="7"></td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['debit']['gross'] ?? 0), 2) }}</td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['debit']['cash'] ?? 0), 2) }}</td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['debit']['bank'] ?? 0), 2) }}</td>
                        <td colspan="6"></td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['credit']['gross'] ?? 0), 2) }}</td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['credit']['cash'] ?? 0), 2) }}</td>
                        <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['credit']['bank'] ?? 0), 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="cashbook-summary">
                <div class="cashbook-summary-title">Cash Book Summary for the Month of {{ $periodLabel }}</div>
                <table>
                    <tr class="gross-heading"><th></th><td>GROSS<br>₦</td></tr>
                    <tr><th>Opening Balance</th><td>{{ number_format((float) $summary['opening_balance'], 2) }}</td></tr>
                    <tr><th>Add: Receipts</th><td>{{ number_format((float) $summary['total_receipts'], 2) }}</td></tr>
                    <tr><th>Funds Available</th><td>{{ number_format((float) $summary['opening_balance'] + (float) $summary['total_receipts'], 2) }}</td></tr>
                    <tr><th>Less: Payment</th><td>{{ number_format((float) $summary['total_payments'], 2) }}</td></tr>
                    <tr class="closing-row"><th>Closing Balance</th><td>{{ number_format((float) $summary['closing_balance'], 2) }}</td></tr>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $entries->links() }}
        </div>
    </div>
@endsection
