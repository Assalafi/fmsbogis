<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BOGIS Cash Receipt</title>

    <style>
        /*
        |--------------------------------------------------------------------------
        | DOMPDF SAFE LAYOUT
        |--------------------------------------------------------------------------
        | Deliberately uses only fixed mm dimensions, absolute positioning,
        | tables, borders and built-in fonts. No flexbox/grid/calculated widths.
        */

        @page {
            size: A4 portrait;
            margin: 8mm 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            color: #111;
            background: #fff;
            font-family: Helvetica, Arial, sans-serif;
        }

        .sheet {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Each half is almost the exact aspect ratio of the physical receipt. */
        .receipt {
            position: relative;
            width: 194mm;
            height: 139.5mm;
            margin: 0 auto;
            padding: 0;
            overflow: hidden;
            background: #fff;
            page-break-inside: avoid;
        }

        .top-green {
            position: absolute;
            left: 0;
            top: 0;
            width: 194mm;
            height: 1.2mm;
            background: #07582b;
        }

        .top-gold {
            position: absolute;
            left: 0;
            top: 1.2mm;
            width: 194mm;
            height: 0.45mm;
            background: #d49a29;
        }

        .copy-badge {
            position: absolute;
            right: 1.5mm;
            top: 2.1mm;
            width: 20mm;
            height: 5mm;
            border: 0.25mm solid #0b6b38;
            border-radius: 1mm;
            color: #0b6b38;
            background: #fff;
            text-align: center;
            line-height: 4.6mm;
            font-size: 5.1pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
            z-index: 20;
        }

        /* Header */
        .bogis-logo {
            position: absolute;
            left: 5mm;
            top: 6mm;
            width: 25mm;
            height: 25mm;
        }

        .coat-of-arms {
            position: absolute;
            right: 6mm;
            top: 8mm;
            width: 18mm;
            height: 21mm;
        }

        .org-name {
            position: absolute;
            left: 32mm;
            top: 9mm;
            width: 130mm;
            text-align: center;
            color: #07582b;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11.2pt;
            font-weight: bold;
            line-height: 1.08;
        }

        .org-address {
            position: absolute;
            left: 32mm;
            top: 17.5mm;
            width: 130mm;
            text-align: center;
            color: #222;
            font-size: 6.8pt;
            line-height: 1.25;
        }

        .org-email {
            position: absolute;
            left: 32mm;
            top: 23mm;
            width: 130mm;
            text-align: center;
            color: #222;
            font-size: 6.6pt;
        }

        .org-email strong {
            color: #d31319;
        }

        .cash-title {
            position: absolute;
            left: 70mm;
            top: 31mm;
            width: 54mm;
            height: 10mm;
            border-radius: 2mm;
            background: #d7101c;
            color: #fff;
            text-align: center;
            line-height: 10mm;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.2pt;
        }

        /* Main receipt fields */
        .main-label {
            position: absolute;
            left: 7mm;
            width: 34mm;
            height: 5mm;
            font-family: Times, "Times New Roman", serif;
            font-size: 9.4pt;
            font-weight: bold;
            font-style: italic;
            line-height: 5mm;
            white-space: nowrap;
        }

        .main-value {
            position: absolute;
            left: 39mm;
            width: 113mm;
            border-bottom: 0.35mm dotted #222;
            padding: 0 1mm 0.9mm 1mm;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.3pt;
            font-weight: bold;
            line-height: 4.4mm;
        }

        .field-received-label { top: 49mm; }
        .field-received-value { top: 49mm; height: 7mm; }

        .field-sum-label { top: 61mm; }
        .field-sum-value {
            top: 60.5mm;
            height: 9.5mm;
            line-height: 4.2mm;
        }

        .field-sum-value.small {
            font-size: 7.6pt;
            line-height: 4mm;
        }

        .field-purpose-label { top: 76mm; }
        .field-purpose-value {
            top: 75.5mm;
            height: 13mm;
            line-height: 4.1mm;
        }

        .field-purpose-value.small {
            font-size: 7.5pt;
            line-height: 3.8mm;
        }

        .field-purpose-value.xsmall {
            font-size: 6.8pt;
            line-height: 3.5mm;
        }

        /* Amount in figures */
        .amount-caption {
            position: absolute;
            left: 83mm;
            top: 91.8mm;
            width: 34mm;
            text-align: right;
            color: #777;
            font-size: 5.4pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
            text-transform: uppercase;
        }

        .amount-box {
            position: absolute;
            left: 118mm;
            top: 89.5mm;
            width: 34mm;
            height: 9mm;
            border: 0.35mm solid #08743a;
            background: #f6fbf7;
            color: #07602f;
            text-align: right;
            padding: 1.4mm 2mm 0 2mm;
            font-size: 11.5pt;
            font-weight: bold;
        }

        .main-separator {
            position: absolute;
            left: 7mm;
            top: 101mm;
            width: 145mm;
            height: 0.65mm;
            background: #111;
        }

        /* QR + signatures */
        .qr-image {
            position: absolute;
            left: 10mm;
            top: 107mm;
            width: 20mm;
            height: 20mm;
        }

        .qr-caption {
            position: absolute;
            left: 8mm;
            top: 128.3mm;
            width: 24mm;
            text-align: center;
            color: #666;
            font-size: 4.5pt;
            font-weight: bold;
            letter-spacing: 0.3pt;
        }

        .signature-label {
            position: absolute;
            height: 5mm;
            font-family: Times, "Times New Roman", serif;
            font-size: 8.2pt;
            font-weight: bold;
            font-style: italic;
            line-height: 5mm;
            white-space: nowrap;
        }

        .signature-value {
            position: absolute;
            height: 5mm;
            border-bottom: 0.3mm solid #222;
            text-align: center;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 6.5pt;
            line-height: 4.2mm;
        }

        .payer-sign-label { left: 37mm; top: 108mm; }
        .payer-sign-line  { left: 67mm; top: 108mm; width: 35mm; }

        .payer-phone-label { left: 37mm; top: 119mm; }
        .payer-phone-line  { left: 67mm; top: 119mm; width: 35mm; }

        .collector-label { left: 108mm; top: 108mm; }
        .collector-line  { left: 137mm; top: 108mm; width: 15mm; }

        .date-label { left: 108mm; top: 119mm; }
        .date-line  { left: 137mm; top: 119mm; width: 15mm; }

        /* Right control panel */
        .right-divider {
            position: absolute;
            left: 156mm;
            top: 47mm;
            width: 0.55mm;
            height: 77mm;
            background: #111;
        }

        .side-item {
            position: absolute;
            left: 160mm;
            width: 28mm;
            text-align: center;
        }

        .receipt-no-label {
            top: 48mm;
            color: #777;
            font-size: 4.8pt;
            font-weight: bold;
            letter-spacing: 0.5pt;
        }

        .receipt-no-value {
            top: 52mm;
            min-height: 7mm;
            font-size: 7.2pt;
            font-weight: bold;
            line-height: 3.2mm;
            word-wrap: break-word;
        }

        .receipt-no-value.small { font-size: 6.3pt; }

        .side-value {
            height: 6mm;
            border-bottom: 0.3mm solid #222;
            padding: 0.5mm 0.4mm 0 0.4mm;
            font-size: 6.2pt;
            font-weight: bold;
            line-height: 4mm;
            word-wrap: break-word;
        }

        .side-value.small { font-size: 5.5pt; }
        .side-value.xsmall { font-size: 4.9pt; }

        .side-label {
            height: 4.5mm;
            font-family: Times, "Times New Roman", serif;
            font-size: 6.5pt;
            font-weight: bold;
            line-height: 4mm;
        }

        .station-value { top: 61mm; }
        .station-label { top: 67mm; }

        .month-value { top: 74mm; }
        .month-label { top: 80mm; }

        .rv-value { top: 87mm; }
        .rv-label { top: 93mm; }

        .head-value { top: 100mm; }
        .head-label { top: 106mm; }

        .subhead-value { top: 113mm; }
        .subhead-label { top: 119mm; }

        /* Footer */
        .receipt-footer {
            position: absolute;
            left: 6mm;
            right: 6mm;
            bottom: 2mm;
            border-top: 0.15mm solid #ddd;
            padding-top: 0.7mm;
            text-align: center;
            color: #999;
            font-size: 4.4pt;
        }

        /* Cut area */
        .cut-area {
            position: relative;
            width: 194mm;
            height: 2mm;
            margin: 0 auto;
            text-align: center;
        }

        .cut-area:before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 1mm;
            border-top: 0.25mm dashed #aaa;
        }

        .cut-area span {
            position: relative;
            top: 0.3mm;
            background: #fff;
            padding: 0 4mm;
            color: #999;
            font-size: 4.5pt;
            letter-spacing: 0.4pt;
        }
    </style>
</head>
<body>

@php
    $date = \Carbon\Carbon::parse($receipt->date_of_transaction);

    $receiptNo = $receipt->receipt_number
        ?? $receipt->treasury_receipt_voucher_number
        ?? '';

    $rvNo = $receipt->treasury_receipt_voucher_number ?? '';

    $station = $receipt->station
        ?? optional($receipt->account)->account_name
        ?? 'BOGIS';

    $headNo = $receipt->head_no
        ?? optional($receipt->economicCode)->code
        ?? '';

    $subHeadNo = $receipt->sub_head_no ?? '';

    $payer = $receipt->from_whom_received_to_whom_paid ?? '';
    $payerPhone = $receipt->payer_phone ?? '';

    $collectorName = optional($receipt->approver)->name
        ?? optional($receipt->creator)->name
        ?? '';

    $paymentFor = $receipt->details
        ?? optional($receipt->economicCode)->name
        ?? '';

    $amount = round((float) $receipt->amount, 2);
    $wholeNaira = (int) floor($amount);
    $kobo = (int) round(($amount - $wholeNaira) * 100);

    if ($kobo >= 100) {
        $wholeNaira++;
        $kobo = 0;
    }

    /* Fallback number-to-words when PHP intl is not installed. */
    $numberToWords = function ($number) use (&$numberToWords) {
        $number = (int) $number;

        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
            17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];

        $tens = [
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
            60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $ten = ((int) floor($number / 10)) * 10;
            $remainder = $number % 10;
            return $tens[$ten] . ($remainder ? ' ' . $ones[$remainder] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;
            return $ones[$hundreds] . ' Hundred'
                . ($remainder ? ' and ' . $numberToWords($remainder) : '');
        }

        $scales = [
            1000000000000 => 'Trillion',
            1000000000 => 'Billion',
            1000000 => 'Million',
            1000 => 'Thousand',
        ];

        foreach ($scales as $scale => $name) {
            if ($number >= $scale) {
                $major = (int) floor($number / $scale);
                $remainder = $number % $scale;

                return $numberToWords($major) . ' ' . $name
                    . ($remainder ? ' ' . $numberToWords($remainder) : '');
            }
        }

        return (string) $number;
    };

    if (class_exists(\NumberFormatter::class)) {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $nairaWords = ucwords($formatter->format($wholeNaira));
        $koboWords = $kobo > 0 ? ucwords($formatter->format($kobo)) : '';
    } else {
        $nairaWords = $numberToWords($wholeNaira);
        $koboWords = $kobo > 0 ? $numberToWords($kobo) : '';
    }

    $amountInWords = $kobo > 0
        ? $nairaWords . ' Naira and ' . $koboWords . ' Kobo Only'
        : $nairaWords . ' Naira Only';

    /* Dynamic font helpers for real-world long values. */
    $sumLength = mb_strlen($amountInWords);
    $sumClass = $sumLength > 85 ? 'small' : '';

    $purposeLength = mb_strlen($paymentFor);
    $purposeClass = $purposeLength > 175
        ? 'xsmall'
        : ($purposeLength > 110 ? 'small' : '');

    $receiptNoLength = mb_strlen($receiptNo);
    $receiptNoClass = $receiptNoLength > 18 ? 'small' : '';

    $stationLength = mb_strlen($station);
    $stationClass = $stationLength > 24
        ? 'xsmall'
        : ($stationLength > 18 ? 'small' : '');

    $rvLength = mb_strlen($rvNo);
    $rvClass = $rvLength > 22
        ? 'xsmall'
        : ($rvLength > 17 ? 'small' : '');

    $copies = ['BOGIS COPY', "PAYER'S COPY"];
@endphp

<div class="sheet">

    @foreach($copies as $index => $copy)

        <div class="receipt">
            <div class="top-green"></div>
            <div class="top-gold"></div>

            <div class="copy-badge">{{ $copy }}</div>

            <img
                class="bogis-logo"
                src="{{ public_path('images/bogis/bogis-logo.png') }}"
                alt="BOGIS Logo"
            >

            <img
                class="coat-of-arms"
                src="{{ public_path('images/bogis/nigeria-coat-of-arms.png') }}"
                alt="Nigeria Coat of Arms"
            >

            <div class="org-name">
                BORNO GEOGRAPHIC INFORMATION SERVICE (BOGIS)
            </div>

            <div class="org-address">
                Along Biu Road, P.M.B 1081, Maiduguri, Borno State
            </div>

            <div class="org-email">
                <strong>Email:</strong> info@bogis.org
            </div>

            <div class="cash-title">CASH RECEIPT</div>

            {{-- Main receipt information --}}
            <div class="main-label field-received-label">Cash Received From:</div>
            <div class="main-value field-received-value">{{ $payer }}</div>

            <div class="main-label field-sum-label">The Sum of:</div>
            <div class="main-value field-sum-value {{ $sumClass }}">
                {{ $amountInWords }}
            </div>

            <div class="main-label field-purpose-label">Being Payment For:</div>
            <div class="main-value field-purpose-value {{ $purposeClass }}">
                {{ $paymentFor }}
            </div>

            <div class="amount-caption">Amount Received</div>
            <div class="amount-box"><span style="font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;">{{ "\u{20A6}" }}</span>{{ number_format($amount, 2) }}</div>

            <div class="main-separator"></div>

            {{-- QR verification --}}
            @if(isset($qrDataUri) && $qrDataUri)
                <img
                    class="qr-image"
                    src="{{ $qrDataUri }}"
                    alt="Receipt QR"
                >
                <div class="qr-caption">SCAN TO VERIFY</div>
            @endif

            {{-- Signatures --}}
            <div class="signature-label payer-sign-label">Signature of Payer:</div>
            <div class="signature-value payer-sign-line"></div>

            <div class="signature-label payer-phone-label">Phone Number:</div>
            <div class="signature-value payer-phone-line">{{ $payerPhone }}</div>

            <div class="signature-label collector-label">Revenue Collector:</div>
            <div class="signature-value collector-line">{{ $collectorName }}</div>

            <div class="signature-label date-label">Date:</div>
            <div class="signature-value date-line">{{ $date->format('d/m/Y') }}</div>

            {{-- Right control panel --}}
            <div class="right-divider"></div>

            <div class="side-item receipt-no-label">RECEIPT NO.</div>
            <div class="side-item receipt-no-value {{ $receiptNoClass }}">
                {{ $receiptNo }}
            </div>

            <div class="side-item side-value station-value {{ $stationClass }}">
                {{ $station }}
            </div>
            <div class="side-item side-label station-label">STATION</div>

            <div class="side-item side-value month-value">
                {{ strtoupper($date->format('F')) }}
            </div>
            <div class="side-item side-label month-label">MONTH</div>

            <div class="side-item side-value rv-value {{ $rvClass }}">
                {{ $rvNo ?: '—' }}
            </div>
            <div class="side-item side-label rv-label">R.V NO.</div>

            <div class="side-item side-value head-value">
                {{ $headNo ?: '—' }}
            </div>
            <div class="side-item side-label head-label">HEAD NO.</div>

            <div class="side-item side-value subhead-value">
                {{ $subHeadNo ?: '—' }}
            </div>
            <div class="side-item side-label subhead-label">SUB-HEAD NO.</div>

            <div class="receipt-footer">
                Borno Geographic Information Service &nbsp; • &nbsp; Official Cash Receipt
            </div>
        </div>

        @if($index === 0)
            <div class="cut-area">
                <span>CUT HERE &nbsp; • &nbsp; BOGIS COPY / PAYER'S COPY</span>
            </div>
        @endif

    @endforeach

</div>

</body>
</html>
