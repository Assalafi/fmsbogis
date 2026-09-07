<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cashbook — {{ $account->account_name }}</title>
    <style>
        @page { size: A4 landscape; margin: 6mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: DejaVu Sans, sans-serif; font-size: 5.5pt; }
        table.cashbook { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.cashbook th, table.cashbook td { border: 0.3pt solid #222; padding: 2px; vertical-align: bottom; overflow-wrap: break-word; }
        table.cashbook .document-title th { border-left: 0; border-right: 0; padding: 1px; text-align: center; font-size: 10pt; font-weight: bold; }
        table.cashbook .spacer-row th { height: 8px; }
        table.cashbook .mda-row th { height: 18px; font-weight: bold; }
        table.cashbook .mda-value { text-align: left; }
        table.cashbook .mda-code-label { text-align: right; }
        table.cashbook .mda-code-value { text-align: center; }
        table.cashbook .ledger-side th { height: 13px; text-align: left; font-weight: bold; }
        table.cashbook .ledger-side .credit-label { text-align: right; }
        table.cashbook .column-headings th { height: 39px; text-align: center; font-weight: bold; }
        table.cashbook tbody td { height: 15px; }
        table.cashbook .amount { text-align: right; white-space: nowrap; }
        table.cashbook .center { text-align: center; }
        table.cashbook .ledger-total td { font-weight: bold; }
        .cashbook-summary { width: 32%; margin: 12px 0 0 24%; font-size: 6.5pt; page-break-inside: avoid; }
        .cashbook-summary-title { margin-bottom: 3px; font-weight: bold; text-transform: uppercase; }
        .cashbook-summary table { width: 100%; border-collapse: collapse; }
        .cashbook-summary th, .cashbook-summary td { padding: 2px 3px; }
        .cashbook-summary th { text-align: left; font-weight: normal; }
        .cashbook-summary td { text-align: right; }
        .cashbook-summary .gross-heading th, .cashbook-summary .gross-heading td,
        .cashbook-summary .closing-row th, .cashbook-summary .closing-row td { font-weight: bold; }
    </style>
</head>
<body>
    <table class="cashbook">
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
                <tr><td colspan="19" style="height:30px; text-align:center;">No cashbook entries found for this account.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="ledger-total">
                <td colspan="7"></td>
                <td class="amount">{{ number_format((float) $summary['total_receipts'], 2) }}</td>
                <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['debit']['cash'] ?? 0), 2) }}</td>
                <td class="amount">{{ number_format((float) $cashbookRows->sum(fn ($row) => $row['debit']['bank'] ?? 0), 2) }}</td>
                <td colspan="6"></td>
                <td class="amount">{{ number_format((float) $summary['total_payments'], 2) }}</td>
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
</body>
</html>
