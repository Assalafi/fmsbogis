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

    $feeType = (string) ($receipt->details ?? '');

    /*
     * Normalize the legacy "Imported from BOGIS Forms — X (CARD payment)..." format.
     */
    if (preg_match('/Imported from BOGIS Forms — (.*?) \([A-Z]+ payment\)/u', $feeType, $m)) {
        $feeType = trim($m[1]);
    }

    /*
     * Application fee payments (including land use fees) are shown simply
     * as "Application Fee". Plot premium / allocation fee payments keep
     * their plot details (scheme, block and plot number).
     */
    $isPremium = stripos($feeType, 'Allocation Fee') !== false
        || stripos($feeType, 'Plot Premium') !== false;

    if ($isPremium) {
        $paymentFor = $feeType !== ''
            ? $feeType
            : (string) (optional($receipt->economicCode)->name ?? '');
    } else {
        $paymentFor = 'Application Fee';
    }

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

    // When $singleCopy is true, only the PAYER'S COPY is rendered
    // (used for applicant downloads).
    $singleCopy = $singleCopy ?? false;

    if ($singleCopy) {
        $copies = ["PAYER'S COPY"];
    }
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
