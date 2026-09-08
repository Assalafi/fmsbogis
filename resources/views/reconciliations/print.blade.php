<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation Statement</title>
    <style>
        @page { margin: 22mm 15mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #17212b; }
        .header { text-align: center; border-bottom: 3px double #17212b; padding-bottom: 9px; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 17px; text-transform: uppercase; }
        .header h2 { margin: 4px 0 0; font-size: 13px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .meta { margin-bottom: 13px; }
        .meta td { width: 33.33%; padding: 3px 5px 3px 0; vertical-align: top; }
        .muted { color: #637083; font-size: 9px; display: block; text-transform: uppercase; }
        .summary { margin-bottom: 16px; }
        .summary td { padding: 5px 7px; border-bottom: 1px solid #d9dee5; }
        .summary .label { width: 75%; }
        .num { text-align: right; white-space: nowrap; }
        .indent { padding-left: 25px !important; }
        .subtotal td { background: #edf3f8; border-top: 1px solid #687687; border-bottom: 1px solid #687687; font-weight: bold; }
        .difference td { background: {{ \App\Support\Money::isZero($reconciliation->difference) ? '#e8f5e9' : '#fdecec' }}; border: 2px solid {{ \App\Support\Money::isZero($reconciliation->difference) ? '#37834a' : '#b83c3c' }}; font-size: 12px; font-weight: bold; }
        .section-title { margin: 14px 0 6px; padding: 5px 7px; background: #273b4f; color: white; font-size: 11px; text-transform: uppercase; }
        .items th, .items td { border: 1px solid #b9c1ca; padding: 4px 5px; vertical-align: top; }
        .items th { background: #edf1f5; font-size: 9px; text-transform: uppercase; }
        .items .amount { text-align: right; width: 15%; white-space: nowrap; }
        .items .classification { width: 19%; }
        .items .effect { width: 19%; }
        .signatures { margin-top: 42px; display: table; width: 100%; }
        .signatures > div { display: table-cell; width: 50%; text-align: center; }
        .signature-line { border-top: 1px solid #17212b; margin: 0 30px; padding-top: 4px; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; color: #707b87; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Borno State Geographic Information Service</h1>
        <h2>Bank Reconciliation Statement</h2>
    </div>

    <table class="meta">
        <tr>
            <td><span class="muted">Account</span><strong>{{ $reconciliation->account->account_name }}</strong></td>
            <td><span class="muted">Bank</span><strong>{{ $reconciliation->account->bank_name }}</strong></td>
            <td><span class="muted">Account Number</span><strong>{{ $reconciliation->account->account_number }}</strong></td>
        </tr>
        <tr>
            <td><span class="muted">Statement Period</span><strong>{{ $reconciliation->bankStatement->statement_from->format('d M Y') }} &ndash; {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</strong></td>
            <td><span class="muted">Reconciliation Date</span><strong>{{ $reconciliation->reconciliation_date->format('d M Y') }}</strong></td>
            <td><span class="muted">Status</span><strong>{{ strtoupper($reconciliation->status) }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Reconciliation Summary</div>
    <table class="summary">
        <tr><td class="label"><strong>Cashbook balance as at {{ $reconciliation->bankStatement->statement_to->format('d M Y') }}</strong></td><td class="num"><strong>&#8358;{{ number_format((float) $reconciliation->cashbook_balance, 2) }}</strong></td></tr>
        <tr><td class="indent">Add: Direct bank credits and items in bank not cashbook</td><td class="num">&#8358;{{ number_format((float) $breakdown['cashbook_additions'], 2) }}</td></tr>
        <tr><td class="indent">Less: Bank debits, charges and cashbook deductions</td><td class="num">(&#8358;{{ number_format((float) $breakdown['cashbook_deductions'], 2) }})</td></tr>
        <tr class="subtotal"><td>Adjusted cashbook balance</td><td class="num">&#8358;{{ number_format((float) $reconciliation->adjusted_cashbook_balance, 2) }}</td></tr>
        <tr><td colspan="2" style="height:5px;border:0"></td></tr>
        <tr><td class="label"><strong>Balance as per bank statement</strong></td><td class="num"><strong>&#8358;{{ number_format((float) $reconciliation->bank_statement_balance, 2) }}</strong></td></tr>
        <tr><td class="indent">Add: Uncredited lodgements and items in cashbook not bank</td><td class="num">&#8358;{{ number_format((float) $breakdown['bank_additions'], 2) }}</td></tr>
        <tr><td class="indent">Less: Unpresented payments and bank balance deductions</td><td class="num">(&#8358;{{ number_format((float) $breakdown['bank_deductions'], 2) }})</td></tr>
        <tr class="subtotal"><td>Adjusted bank balance</td><td class="num">&#8358;{{ number_format((float) $reconciliation->adjusted_bank_balance, 2) }}</td></tr>
        <tr class="difference"><td>Difference</td><td class="num">&#8358;{{ number_format((float) $reconciliation->difference, 2) }}</td></tr>
    </table>

    <div class="section-title">Reconciling Adjustments</div>
    <table class="items">
        <thead>
            <tr><th class="classification">Category</th><th>Source / Reference</th><th class="effect">Effect</th><th>Details</th><th class="amount">Amount</th></tr>
        </thead>
        <tbody>
            @forelse($reconciliation->items as $item)
                <tr>
                    <td>{{ $item->displayType() }}</td>
                    <td>
                        @if($item->cashbookEntry && $item->bankStatementLine)
                            Cashbook {{ $item->cashbookEntry->reference ?: 'entry' }} / Bank {{ $item->bankStatementLine->reference ?: $item->bankStatementLine->description }}
                        @elseif($item->cashbookEntry)
                            Cashbook: {{ $item->cashbookEntry->reference ?: $item->cashbookEntry->details }}
                        @elseif($item->bankStatementLine)
                            Bank: {{ $item->bankStatementLine->reference ?: $item->bankStatementLine->description }}
                        @else
                            Manual adjustment
                        @endif
                    </td>
                    <td>{{ $item->effectLabel() }}</td>
                    <td>@if($item->notes){{ $item->notes }}@else&mdash;@endif</td>
                    <td class="amount">&#8358;{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:10px">No reconciliation items recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="signatures">
        <div><div class="signature-line">Prepared by<br><strong>{{ $reconciliation->preparer?->name ?? '________________' }}</strong></div></div>
        <div><div class="signature-line">Approved by<br><strong>{{ $reconciliation->approver?->name ?? '________________' }}</strong></div></div>
    </div>

    <div class="footer">Generated by the BOGIS Finance Management System on {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
