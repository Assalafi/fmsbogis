<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BOGIS Cash Receipt</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 7mm 5mm 7mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111;
            background: #fff;
        }

        table {
            width: 90%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        .page {
            width: 98%;
            margin: 0;
            padding: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | EACH RECEIPT
        |--------------------------------------------------------------------------
        |
        | A4 usable height after page margins is about 287mm.
        | Two receipts + cut space fit perfectly.
        */

        .receipt {
            position: relative;
            width: 90%;
            height: 128mm;
            margin-left: 10mm;
            margin-top: 10mm;

            border: 0.35mm solid #b7b7b7;
            overflow: hidden;

            background: #fff;

            page-break-inside: avoid;
        }

        .receipt-inner {
            position: relative;
            width: 100%;
            height: 100%;

            padding:
                4mm
                5mm
                3mm
                5mm;
        }

        /*
        |--------------------------------------------------------------------------
        | TOP DECORATION
        |--------------------------------------------------------------------------
        */

        .green-strip {
            position: absolute;
            top: 0;
            left: 0;

            width: 100%;
            height: 1.2mm;

            background: #08632f;
        }

        .gold-strip {
            position: absolute;
            top: 1.2mm;
            left: 0;

            width: 100%;
            height: 0.45mm;

            background: #da9a25;
        }

        /*
        |--------------------------------------------------------------------------
        | COPY LABEL
        |--------------------------------------------------------------------------
        */

        .copy-badge {
            position: absolute;

            right: 4mm;
            top: 3mm;

            border: 0.3mm solid #08733a;
            border-radius: 1.4mm;

            padding: 0.8mm 2.4mm;

            color: #08733a;
            background: #fff;

            font-size: 6.7px;
            font-weight: 700;

            letter-spacing: 0.5px;

            z-index: 10;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            table-layout: fixed;

            height: 27mm;

            margin-top: 1mm;
        }

        .header-logo {
            width: 15%;
            vertical-align: middle;
            text-align: left;
        }

        .header-logo img {
            width: 24mm;
            height: auto;
        }

        .header-title {
            width: 70%;

            vertical-align: middle;
            text-align: center;

            padding: 0 2mm;
        }

        .header-coat {
            width: 15%;

            vertical-align: middle;
            text-align: right;
        }

        .header-coat img {
            width: 17mm;
            height: auto;
        }

        .organization-name {
            color: #07582b;

            font-size: 14px;
            font-weight: 800;

            line-height: 1.1;

            white-space: nowrap;

            text-transform: uppercase;
        }

        .organization-address {
            margin-top: 1.2mm;

            font-size: 7.6px;
            line-height: 1.35;

            color: #222;
        }

        .organization-email {
            color: #d31319;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | RECEIPT TITLE
        |--------------------------------------------------------------------------
        */

        .receipt-title-wrapper {
            height: 12mm;

            text-align: center;

            padding-top: 1mm;
        }

        .receipt-title {
            display: inline-block;

            background: #da111d;
            color: #fff;

            border-radius: 2.2mm;

            padding:
                1.4mm
                6mm
                1.6mm
                6mm;

            font-size: 16px;
            font-weight: 800;

            line-height: 1;

            letter-spacing: 0.3px;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN BODY
        |--------------------------------------------------------------------------
        */

        .body-layout {
            width: 100%;
            table-layout: fixed;

            height: 89mm;
        }

        .receipt-main {
            width: 79%;

            vertical-align: top;

            padding:
                1mm
                4mm
                0
                1mm;
        }

        .receipt-side {
            width: 21%;

            vertical-align: top;

            border-left: 0.7mm solid #111;

            padding:
                0
                1mm
                0
                3.5mm;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN FORM LINES
        |--------------------------------------------------------------------------
        */

        .form-row {
            width: 100%;
            table-layout: fixed;

            margin-bottom: 2.2mm;
        }

        .form-label {
            width: 30mm;

            vertical-align: bottom;

            padding-bottom: 0.8mm;

            font-family: DejaVu Serif, serif;
            font-size: 9.5px;
            font-weight: 700;
            font-style: italic;

            white-space: nowrap;
        }

        .form-label.top {
            vertical-align: top;

            padding-top: 1.6mm;
        }

        .form-value {
            vertical-align: bottom;

            min-height: 7mm;

            border-bottom: 0.35mm dotted #222;

            padding:
                1mm
                1.5mm
                0.8mm
                1.5mm;

            font-size: 9px;
            font-weight: 700;

            line-height: 1.4;
        }

        /*
        |--------------------------------------------------------------------------
        | SUM IN WORDS
        |--------------------------------------------------------------------------
        */

        .sum-row {
            margin-top: 0.5mm;
            margin-bottom: 2.6mm;
        }

        .sum-label {
            width: 24mm;
        }

        .sum-value {
            min-height: 11mm;

            vertical-align: top;

            border-bottom: 0.35mm dotted #222;

            padding:
                1mm
                1.5mm
                1mm
                1.5mm;

            font-size: 9px;
            font-weight: 700;

            line-height: 1.55;
        }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT PURPOSE
        |--------------------------------------------------------------------------
        */

        .payment-purpose {
            min-height: 11mm;

            vertical-align: top;

            border-bottom: 0.35mm dotted #222;

            padding:
                1mm
                1.5mm
                1mm
                1.5mm;

            font-size: 9px;
            font-weight: 700;

            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | AMOUNT BOX
        |--------------------------------------------------------------------------
        */

        .amount-table {
            width: 100%;

            margin-top: 3mm;
            margin-bottom: 3.5mm;
        }

        .amount-label {
            width: 58%;

            padding-right: 3mm;

            vertical-align: middle;
            text-align: right;

            font-size: 6.5px;
            font-weight: 700;

            color: #777;

            letter-spacing: 0.7px;

            text-transform: uppercase;
        }

        .amount-box {
            width: 42%;

            border: 0.4mm solid #08743a;

            background: #f4faf6;
            color: #07602f;

            padding:
                1.7mm
                2.5mm;

            text-align: right;

            font-size: 13px;
            font-weight: 800;
        }

        /*
        |--------------------------------------------------------------------------
        | SIGNATURE SECTION
        |--------------------------------------------------------------------------
        */

        .signature-section {
            width: 100%;

            border-top: 0.65mm solid #111;

            padding-top: 3mm;
        }

        .signature-table {
            table-layout: fixed;
        }

        .signature-column {
            width: 50%;

            vertical-align: top;
        }

        .signature-column-left {
            padding-right: 4mm;
        }

        .signature-column-right {
            padding-left: 4mm;
        }

        .signature-item {
            margin-bottom: 3mm;

            white-space: nowrap;
        }

        .signature-label {
            display: inline-block;

            font-family: DejaVu Serif, serif;

            font-size: 8.7px;
            font-weight: 700;
            font-style: italic;
        }

        .signature-line {
            display: inline-block;

            width: 37mm;
            height: 5mm;

            margin-left: 1mm;

            border-bottom: 0.3mm solid #222;

            vertical-align: bottom;

            text-align: center;

            padding-bottom: 0.5mm;

            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7.2px;
            font-style: normal;
            font-weight: normal;
        }

        .signature-line-small {
            width: 27mm;
        }

        /*
        |--------------------------------------------------------------------------
        | RIGHT CONTROL PANEL
        |--------------------------------------------------------------------------
        */

        .serial-block {
            text-align: center;

            margin-bottom: 3mm;
        }

        .serial-label {
            margin-bottom: 0.5mm;

            color: #777;

            font-size: 5.7px;
            font-weight: 700;

            letter-spacing: 0.6px;

            text-transform: uppercase;
        }

        .serial-value {
            font-size: 13px;
            font-weight: 800;

            letter-spacing: 0.7px;
        }

        .control-box {
            width: 100%;

            margin-bottom: 2.7mm;

            text-align: center;
        }

        .control-value {
            width: 100%;

            min-height: 6.2mm;

            border-bottom: 0.3mm solid #222;

            padding:
                1mm
                0.8mm
                0.7mm
                0.8mm;

            font-size: 7.8px;
            font-weight: 700;

            line-height: 1.25;

            word-wrap: break-word;
        }

        .control-label {
            padding-top: 0.8mm;

            font-family: DejaVu Serif, serif;
            font-size: 6.8px;
            font-weight: 700;

            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | QR CODE (VERIFICATION)
        |--------------------------------------------------------------------------
        */

        .qr-block {
            margin-top: 3.5mm;

            text-align: center;
        }

        .qr-block img {
            width: 29mm;
            height: 29mm;
        }

        .qr-label {
            margin-top: 0.9mm;

            font-family: DejaVu Serif, serif;
            font-size: 6.3px;
            font-weight: 700;

            letter-spacing: 0.4px;

            text-transform: uppercase;

            color: #666;
        }

        /*
        |--------------------------------------------------------------------------
        | OPTIONAL SIDE INFORMATION
        |--------------------------------------------------------------------------
        */

        .mini-info {
            margin-top: 2.3mm;

            border: 0.25mm solid #bbb;

            padding: 1.1mm;

            text-align: center;
        }

        .mini-label {
            color: #777;

            font-size: 5.4px;

            text-transform: uppercase;
        }

        .mini-value {
            margin-top: 0.5mm;

            font-size: 6.6px;
            font-weight: 700;

            line-height: 1.3;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            position: absolute;

            left: 5mm;
            right: 5mm;
            bottom: 1.5mm;

            border-top: 0.2mm solid #ddd;

            padding-top: 0.7mm;

            text-align: center;

            color: #999;

            font-size: 5.3px;
        }

        /*
        |--------------------------------------------------------------------------
        | CUT LINE
        |--------------------------------------------------------------------------
        */

        .cut-area {
            width: 100%;
            height: 4mm;

            position: relative;

            text-align: center;
        }

        .cut-area:before {
            content: "";

            position: absolute;

            left: 0;
            right: 0;
            top: 2mm;

            border-top: 0.25mm dashed #aaa;
        }

        .cut-label {
            position: relative;

            top: 0.8mm;

            display: inline-block;

            padding: 0 4mm;

            background: #fff;

            color: #999;

            font-size: 5.2px;

            letter-spacing: 0.6px;
        }
    </style>
</head>

<body>

@php

    /*
    |--------------------------------------------------------------------------
    | RECEIPT BASIC DATA
    |--------------------------------------------------------------------------
    */

    $date = \Carbon\Carbon::parse(
        $receipt->date_of_transaction
    );

    $receiptNo =
        $receipt->receipt_number
        ?? $receipt->treasury_receipt_voucher_number
        ?? '';

    $rvNo =
        $receipt->treasury_receipt_voucher_number
        ?? '';

    $station =
        $receipt->station
        ?? optional($receipt->account)->account_name
        ?? 'BOGIS';

    $headNo =
        $receipt->head_no
        ?? optional($receipt->economicCode)->code
        ?? '';

    $subHeadNo =
        $receipt->sub_head_no
        ?? '';

    $payer =
        $receipt->from_whom_received_to_whom_paid
        ?? '';

    $payerPhone =
        $receipt->payer_phone
        ?? '';

    $collectorName =
        optional($receipt->approver)->name
        ?? optional($receipt->creator)->name
        ?? '';

    $paymentFor =
        $receipt->details
        ?? optional($receipt->economicCode)->name
        ?? '';

    $bankSlip =
        $receipt->bank_credit_slip_cheque_mandate_number
        ?? '';

    $expenditureCredits =
        $receipt->expenditure_credits
        ?? '';

    /*
    |--------------------------------------------------------------------------
    | AMOUNT
    |--------------------------------------------------------------------------
    */

    $amount = round(
        (float) $receipt->amount,
        2
    );

    $wholeNaira = (int) floor($amount);

    $kobo = (int) round(
        ($amount - $wholeNaira) * 100
    );

    /*
    |--------------------------------------------------------------------------
    | FALLBACK NUMBER TO WORDS
    |--------------------------------------------------------------------------
    */

    $numberToWords = function ($number) use (&$numberToWords) {

        $number = (int) $number;

        $ones = [
            0  => 'Zero',
            1  => 'One',
            2  => 'Two',
            3  => 'Three',
            4  => 'Four',
            5  => 'Five',
            6  => 'Six',
            7  => 'Seven',
            8  => 'Eight',
            9  => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
        ];

        $tens = [
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {

            $ten = floor($number / 10) * 10;

            $remainder = $number % 10;

            return $tens[$ten]
                . ($remainder
                    ? ' ' . $ones[$remainder]
                    : '');
        }

        if ($number < 1000) {

            $hundreds = floor($number / 100);

            $remainder = $number % 100;

            return $ones[$hundreds]
                . ' Hundred'
                . ($remainder
                    ? ' and ' . $numberToWords($remainder)
                    : '');
        }

        $scales = [
            1000000000000 => 'Trillion',
            1000000000    => 'Billion',
            1000000       => 'Million',
            1000          => 'Thousand',
        ];

        foreach ($scales as $scale => $name) {

            if ($number >= $scale) {

                $major = floor(
                    $number / $scale
                );

                $remainder =
                    $number % $scale;

                return $numberToWords($major)
                    . ' '
                    . $name
                    . ($remainder
                        ? ' ' . $numberToWords($remainder)
                        : '');
            }
        }

        return (string) $number;
    };


    /*
    |--------------------------------------------------------------------------
    | FULL AMOUNT IN WORDS
    |--------------------------------------------------------------------------
    */

    if (class_exists(\NumberFormatter::class)) {

        $formatter = new \NumberFormatter(
            'en',
            \NumberFormatter::SPELLOUT
        );

        $nairaWords = ucwords(
            $formatter->format($wholeNaira)
        );

        $koboWords = $kobo > 0
            ? ucwords(
                $formatter->format($kobo)
            )
            : '';

    } else {

        $nairaWords =
            $numberToWords($wholeNaira);

        $koboWords =
            $kobo > 0
                ? $numberToWords($kobo)
                : '';
    }


    if ($kobo > 0) {

        $amountInWords =
            $nairaWords
            . ' Naira and '
            . $koboWords
            . ' Kobo Only';

    } else {

        $amountInWords =
            $nairaWords
            . ' Naira Only';
    }


    /*
    |--------------------------------------------------------------------------
    | COPIES
    |--------------------------------------------------------------------------
    */

    $copies = [
        'BOGIS COPY',
        "PAYER'S COPY",
    ];

@endphp


<div class="page">

    @foreach($copies as $index => $copy)

        <div class="receipt">

            <div class="green-strip"></div>
            <div class="gold-strip"></div>


            <div class="copy-badge">
                {{ $copy }}
            </div>


            <div class="receipt-inner">

                {{-- =====================================================
                    HEADER
                ====================================================== --}}

                <table class="header">

                    <tr>

                        <td class="header-logo">

                            <img
                                src="{{ public_path('images/bogis/bogis-logo.png') }}"
                            >

                        </td>


                        <td class="header-title">

                            <div class="organization-name">

                                BORNO GEOGRAPHIC INFORMATION SERVICE (BOGIS)

                            </div>


                            <div class="organization-address">

                                Along Biu Road, P.M.B 1081,
                                Maiduguri, Borno State

                                <br>

                                <span class="organization-email">
                                    Email:
                                </span>

                                info@bogis.org

                            </div>

                        </td>


                        <td class="header-coat">

                            <img
                                src="{{ public_path('images/bogis/nigeria-coat-of-arms.png') }}"
                            >

                        </td>

                    </tr>

                </table>


                {{-- =====================================================
                    TITLE
                ====================================================== --}}

                <div class="receipt-title-wrapper">

                    <span class="receipt-title">

                        CASH RECEIPT

                    </span>

                </div>


                {{-- =====================================================
                    BODY
                ====================================================== --}}

                <table class="body-layout">

                    <tr>

                        {{-- =============================================
                            MAIN SECTION
                        ============================================== --}}

                        <td class="receipt-main">


                            {{-- Cash Received From --}}

                            <table class="form-row">

                                <tr>

                                    <td class="form-label">

                                        Cash Received From:

                                    </td>

                                    <td class="form-value">

                                        {{ $payer }}

                                    </td>

                                </tr>

                            </table>


                            {{-- The Sum Of --}}

                            <table class="form-row sum-row">

                                <tr>

                                    <td class="form-label sum-label top">

                                        The Sum of:

                                    </td>

                                    <td class="sum-value">

                                        {{ $amountInWords }}

                                    </td>

                                </tr>

                            </table>


                            {{-- Payment Purpose --}}

                            <table class="form-row">

                                <tr>

                                    <td class="form-label top">

                                        Being Payment For:

                                    </td>

                                    <td class="payment-purpose">

                                        {{ $paymentFor }}

                                    </td>

                                </tr>

                            </table>


                            {{-- Amount --}}

                            <table class="amount-table">

                                <tr>

                                    <td class="amount-label">

                                        Amount Received

                                    </td>

                                    <td class="amount-box">

                                        ₦{{ number_format($amount, 2) }}

                                    </td>

                                </tr>

                            </table>


                            {{-- =========================================
                                SIGNATURES
                            ========================================== --}}

                            <div class="signature-section">

                                <table class="signature-table">

                                    <tr>

                                        <td class="signature-column signature-column-left">

                                            <div class="signature-item">

                                                <span class="signature-label">

                                                    Signature of Payer:

                                                </span>

                                                <span class="signature-line">

                                                </span>

                                            </div>


                                            <div class="signature-item">

                                                <span class="signature-label">

                                                    Phone Number:

                                                </span>

                                                <span class="signature-line">

                                                    {{ $payerPhone }}

                                                </span>

                                            </div>

                                        </td>


                                        <td class="signature-column signature-column-right">

                                            <div class="signature-item">

                                                <span class="signature-label">

                                                    Revenue Collector:

                                                </span>

                                                <span class="signature-line">

                                                    {{ $collectorName }}

                                                </span>

                                            </div>


                                            <div class="signature-item">

                                                <span class="signature-label">

                                                    Date:

                                                </span>

                                                <span class="signature-line signature-line-small">

                                                    {{ $date->format('d/m/Y') }}

                                                </span>

                                            </div>

                                        </td>

                                    </tr>

                                </table>

                            </div>

                        </td>


                        {{-- =============================================
                            RIGHT CONTROL PANEL
                        ============================================== --}}

                        <td class="receipt-side">


                            <div class="serial-block">

                                <div class="serial-label">

                                    Receipt No.

                                </div>

                                <div class="serial-value">

                                    {{ $receiptNo }}

                                </div>

                            </div>


                            <div class="control-box">

                                <div class="control-value">

                                    {{ $station }}

                                </div>

                                <div class="control-label">

                                    Station

                                </div>

                            </div>


                            <div class="control-box">

                                <div class="control-value">

                                    {{ strtoupper(
                                        $date->format('F')
                                    ) }}

                                </div>

                                <div class="control-label">

                                    Month

                                </div>

                            </div>


                            <div class="control-box">

                                <div class="control-value">

                                    {{ $rvNo }}

                                </div>

                                <div class="control-label">

                                    R.V No.

                                </div>

                            </div>


                            <div class="control-box">

                                <div class="control-value">

                                    {{ $headNo }}

                                </div>

                                <div class="control-label">

                                    Head No.

                                </div>

                            </div>


                            <div class="control-box">

                                <div class="control-value">

                                    {{ $subHeadNo ?: '—' }}

                                </div>

                                <div class="control-label">

                                    Sub-Head No.

                                </div>

                            </div>


                            @if(isset($qrDataUri) && $qrDataUri)

                                <div class="qr-block">

                                    <img
                                        src="{{ $qrDataUri }}"
                                        alt="Receipt QR"
                                    >

                                    <div class="qr-label">

                                        Scan To Verify

                                    </div>

                                </div>

                            @endif


                            @if(!empty($bankSlip))

                                <div class="mini-info">

                                    <div class="mini-label">

                                        Bank Credit Slip

                                    </div>

                                    <div class="mini-value">

                                        {{ $bankSlip }}

                                    </div>

                                </div>

                            @endif


                            @if(!empty($expenditureCredits))

                                <div class="mini-info">

                                    <div class="mini-label">

                                        Expenditure Credits

                                    </div>

                                    <div class="mini-value">

                                        {{ $expenditureCredits }}

                                    </div>

                                </div>

                            @endif

                        </td>

                    </tr>

                </table>


                <div class="footer">

                    Borno Geographic Information Service •
                    Official Cash Receipt

                </div>

            </div>

        </div>


        @if($index === 0)

            <div class="cut-area">

                <span class="cut-label">

                    CUT HERE &nbsp;&nbsp; • &nbsp;&nbsp;
                    BOGIS COPY / PAYER'S COPY

                </span>

            </div>

        @endif

    @endforeach

</div>

</body>
</html>
