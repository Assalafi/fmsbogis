<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use NumberFormatter;

class BogisCashReceiptPdfController extends Controller
{
    public function show(Receipt $receipt)
    {
        $receipt->load([
            'account',
            'economicCode',
            'creator',
            'approver',
        ]);

        [$amountInWords, $kobo] = $this->amountInWords((float) $receipt->amount);

        $receiptNo = $receipt->receipt_number ?? $receipt->treasury_receipt_voucher_number;

        $qrDataUri = $this->generateQr((string) $receiptNo);

        $pdf = Pdf::loadView('pdf.cash-receipt', [
            'receipt' => $receipt,
            'amountInWords' => $amountInWords,
            'kobo' => $kobo,
            'qrDataUri' => $qrDataUri,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultMediaType', 'print')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream(
            'BOGIS-Cash-Receipt-' . $receipt->treasury_receipt_voucher_number . '.pdf'
        );
    }

    private function generateQr(string $data): string
    {
        $png = $this->fetchQrFromApi($data) ?? $this->generateQrLocally($data);

        if ($png === null || $png === '') {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Try the QR web API (qrserver.com) first.
     */
    private function fetchQrFromApi(string $data): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('https://api.qrserver.com/v1/create-qr-code/', [
                    'size' => '174x174',
                    'data' => $data,
                    'margin' => 1,
                ]);

            if ($response->successful() && strlen((string) $response->body()) > 100) {
                return $response->body();
            }
        } catch (\Throwable) {
            // fall through to local generation
        }

        return null;
    }

    /**
     * Fallback: generate locally with chillerlan + GD.
     */
    private function generateQrLocally(string $data): ?string
    {
        try {
            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'eccLevel' => EccLevel::M,
                'scale' => 6,
                'margin' => 1,
            ]);

            $uri = (new QRCode($options))->render($data);

            if (str_starts_with($uri, 'data:image/png;base64,')) {
                return base64_decode(substr($uri, strlen('data:image/png;base64,')));
            }

            return $uri;
        } catch (\Throwable) {
            return null;
        }
    }

    private function amountInWords(float $amount): array
    {
        $naira = (int) floor($amount);
        $kobo = (int) round(($amount - $naira) * 100);

        // Handle 99.999 rounding to the next Naira.
        if ($kobo >= 100) {
            $naira++;
            $kobo = 0;
        }

        $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        $nairaWords = ucwords($formatter->format($naira));

        if ($kobo > 0) {
            $koboWords = ucwords($formatter->format($kobo));
            $words = $nairaWords . ' Naira And ' . $koboWords . ' Kobo Only';
        } else {
            $words = $nairaWords . ' Naira Only';
        }

        return [$words, $kobo];
    }
}
