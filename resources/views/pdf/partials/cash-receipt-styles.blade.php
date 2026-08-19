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
            font-size: 13pt;
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
            font-size: 8.2pt;
            line-height: 1.25;
        }

        .org-email {
            position: absolute;
            left: 32mm;
            top: 23mm;
            width: 130mm;
            text-align: center;
            color: #222;
            font-size: 8pt;
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
            font-size: 11pt;
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
            font-size: 9.8pt;
            font-weight: bold;
            line-height: 4.4mm;
            text-align: center;
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
            font-size: 6.5pt;
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
            text-align: center;
            padding: 1.4mm 2mm 0 2mm;
            font-size: 13pt;
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
            font-size: 9.5pt;
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
            font-size: 8pt;
            line-height: 4.2mm;
        }

        .payer-sign-label { left: 37mm; top: 108mm; }
        .payer-sign-line  { left: 67mm; top: 108mm; width: 35mm; }

        .payer-phone-label { left: 37mm; top: 119mm; }
        .payer-phone-line  { left: 67mm; top: 119mm; width: 35mm; }

        .collector-label { left: 103mm; top: 108mm; }
        .collector-line  { left: 137mm; top: 108mm; width: 18mm; }

        .date-label { left: 103mm; top: 119mm; }
        .date-line  { left: 137mm; top: 119mm; width: 18mm; }

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