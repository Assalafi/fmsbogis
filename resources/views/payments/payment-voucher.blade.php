<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Payment Voucher</title>
    <style>
        @page {
            margin: 20mm 15mm;
            size: A4 portrait;
        }

        * {
            margin: 2.5mm;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table {
            margin-bottom: 15px;
        }

        .header-table td {
            padding: 6px;
            text-align: center;
        }

        .header-table .left-align {
            text-align: left;
            font-size: 6pt;
        }

        h2 {
            font-size: 14pt;
            margin: 8px 0;
            font-weight: bold;
        }

        .main-table {
            border: 1px solid #000;
        }

        .main-table>tbody>tr>td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        .left-column {
            width: 70%;
        }

        .right-column {
            width: 30%;
        }

        .inner-table td {
            padding: 4px 0;
            border: none;
        }

        .details-table {
            margin: 8px 0;
            border: 1px solid #000;
        }

        .details-table td,
        .details-table th {
            border: 1px solid #000;
            padding: 4px;
            font-size: 8pt;
        }

        .details-table th {
            font-weight: bold;
            text-align: left;
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .certification-box,
        .certify-box,
        .receipt-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 8px 0;
            font-size: 9pt;
        }

        .right-box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 9pt;
        }

        .right-box-tall {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 9pt;
            min-height: 60px;
        }

        .right-box-medium {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 9pt;
            min-height: 50px;
        }

        .stamp-box {
            border: 1px solid #000;
            padding: 20px;
            margin-bottom: 8px;
            text-align: center;
            font-size: 8pt;
            min-height: 70px;
        }

        .audit-box {
            border: 1px solid #000;
            padding: 15px;
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
        }

        p {
            margin: 5px 0;
        }

        strong {
            font-weight: bold;
        }

        img {
            max-width: 100px;
            height: auto;
        }

        .divider {
            border-top: 1px solid #000;
            margin-top: 15px;
            padding-top: 8px;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td>
                <img src="{{ public_path('assets/images/logo-icon.png') }}" alt="Logo">
            </td>
        </tr>
        <tr>
            <td>
                <strong>BOGIS/{{ $payment->economicCode?->code ?? 'N/A' }}/{{ $payment->date_of_transaction->format('m') }}/{{ $payment->date_of_transaction->format('Y') }}/{{ $payment->treasury_receipt_voucher_number }}/{{ $payment->dept_voucher_number ?? 'N/A' }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <h2>PAYMENT VOUCHER</h2>
            </td>
        </tr>
        <tr>
            <td class="left-align">
                <strong>TBS 44</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>ORIGINAL</strong>
            </td>
        </tr>
    </table>

    <!-- MAIN CONTENT -->
    <table class="main-table">
        <tbody>
            <tr>
                <!-- LEFT COLUMN -->
                <td class="left-column">
                    <table class="inner-table">
                        <tr>
                            <td><strong>Payment made to:</strong> {{ $payment->from_whom_received_to_whom_paid ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Address:</strong> {{ $payment->account?->bank_name ?? '—' }} ({{ $payment->account?->account_number ?? '—' }})</td>
                        </tr>
                    </table>

                    <!-- DETAILS TABLE -->
                    <table class="details-table">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Date</th>
                                <th style="width: 50%;">Detailed description</th>
                                <th style="width: 15%;" class="text-right">Rate</th>
                                <th style="width: 18%;" class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $payment->date_of_transaction->format('d/m/Y') }}</td>
                                <td>{{ $payment->details }}</td>
                                <td class="text-right"></td>
                                <td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>

                            <tr>
                                <td colspan="2"><strong>TOTAL AMOUNT IN WORDS:</strong>
                                    {{ $amountInWords }}</td>
                                <td colspan="2" class="text-right"><strong>{{ number_format((float) $payment->amount, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- CERTIFICATION -->
                    <div class="certification-box">
                        <p style="font-size: 7pt;">Certified that the details above are in accordance with the relevant
                            contract, regulation or other authority under which the Services/Goods were
                            provided/purchased.</p>
                        <p style="margin-top: 5px;">Officer who prepared voucher:<br>
                            Signature: ___________________<br>
                            Name (BLOCK): {{ $payment->creator?->name ?? '' }}</p>
                        <p style="margin-top: 4px;">Officer who checked voucher:<br>
                            Signature: ___________________<br>
                            Name (BLOCK): {{ $payment->approver?->name ?? '' }}</p>
                    </div>

                    <!-- I CERTIFY -->
                    <div class="certify-box">
                        <p>I certify that the Service/Goods have been duly performed/received, that financial authority
                            is held to incur this expenditure and that the relevant D.V.E. Account entries have been
                            made.</p>
                        <p style="margin-top: 4px;">Signature: _________________ for</p>
                        <p>Name (In Blocks): _________________ Date: _________</p>
                    </div>

                    <!-- RECEIPT -->
                    <div class="receipt-box">
                        <p>Received the sum of _________________ Naira _______ and _______ kobo in payment of the above
                            account this _____ day of ____________ 20____</p>
                        <p style="margin-top: 4px;">Witness to mark ____________ (Signature) (Signature of Payee)</p>
                        <p>Witnessing Name: _______________________</p>
                        <p>Official Rank: _________________________</p>
                    </div>
                </td>

                <!-- RIGHT COLUMN -->
                <td class="right-column">
                    <div class="right-box">
                        <strong>Station</strong><br>
                        <strong>BOGIS</strong>
                    </div>

                    <div class="right-box">
                        {{ $payment->date_of_transaction->format('F Y') }}
                    </div>

                    <div class="right-box">
                        PV No. {{ $payment->treasury_receipt_voucher_number }}<br>
                        DV No. {{ $payment->dept_voucher_number ?? '—' }}<br>
                        Date: {{ $payment->date_of_transaction->format('d/m/Y') }}
                    </div>

                    <div class="right-box">
                        MDA Code: {{ $payment->economicCode?->code ?? '—' }}
                    </div>

                    <div class="right-box-tall">
                        {{ $payment->economicCode?->name ?? '' }}
                        <div class="divider">
                            TREASURY CHECKING OFFICER
                        </div>
                    </div>

                    <div class="right-box-medium">
                        CHEQUE NO<br>
                        {{ $payment->bank_credit_slip_cheque_mandate_number ?? '' }}
                    </div>

                    <div class="stamp-box">
                        (Space for Treasury Stamp)
                    </div>

                    <div class="audit-box">
                        INTERNAL AUDIT<br>VERIFICATION
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
