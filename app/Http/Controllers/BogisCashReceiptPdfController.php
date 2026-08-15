<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $pdf = Pdf::loadView('pdf.cash-receipt', [
            'receipt' => $receipt,
            'amountInWords' => $amountInWords,
            'kobo' => $kobo,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->stream(
            'BOGIS-Cash-Receipt-' . $receipt->treasury_receipt_voucher_number . '.pdf'
        );
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
