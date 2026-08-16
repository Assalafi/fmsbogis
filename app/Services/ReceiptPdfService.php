<?php

namespace App\Services;

use App\Models\Receipt;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPdfService
{
    /**
     * Receipts are merged into a single PDF; larger sets are split
     * into multiple PDF files of this many receipts each.
     */
    public const MERGE_CHUNK_SIZE = 100;

    /**
     * Generate the official BOGIS cash receipt PDF.
     */
    public function generate(Receipt $receipt): string
    {
        $receipt->load([
            'account',
            'economicCode',
            'creator',
            'approver',
        ]);

        $receiptNo = $receipt->receipt_number ?? $receipt->treasury_receipt_voucher_number;

        $qrDataUri = $this->generateQr((string) $receiptNo);

        return $this->renderPdf($this->fullHtml($this->renderBody($receipt, $qrDataUri)));
    }

    /**
     * Merge many receipts into a single multi-page PDF (one receipt per page).
     *
     * @param  iterable<Receipt>  $receipts
     */
    public function generateMerged(iterable $receipts): string
    {
        $body = '';
        $first = true;

        foreach ($receipts as $receipt) {
            if (! $first) {
                $body .= '<div style="page-break-after: always;"></div>';
            }
            $first = false;

            $receipt->load([
                'account',
                'economicCode',
                'creator',
                'approver',
            ]);

            $receiptNo = $receipt->receipt_number ?? $receipt->treasury_receipt_voucher_number;

            // Local generation only: keeps bulk rendering fast and offline-safe.
            $qrDataUri = $this->generateQrLocally((string) $receiptNo);

            $body .= $this->renderBody($receipt, $qrDataUri);
        }

        return $this->renderPdf($this->fullHtml($body));
    }

    /**
     * Filename for a receipt PDF download.
     */
    public function filename(Receipt $receipt): string
    {
        $no = preg_replace('/[^A-Za-z0-9\-_]+/', '-', $receipt->treasury_receipt_voucher_number);

        return 'BOGIS-Cash-Receipt-'.$no.'.pdf';
    }

    protected function renderBody(Receipt $receipt, string $qrDataUri): string
    {
        return view('pdf.partials.cash-receipt-body', compact('receipt', 'qrDataUri'))->render();
    }

    protected function fullHtml(string $body): string
    {
        $styles = view('pdf.partials.cash-receipt-styles')->render();

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>BOGIS Cash Receipt</title>'.$styles.'</head><body>'.$body.'</body></html>';
    }

    protected function renderPdf(string $html): string
    {
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultMediaType', 'print')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->output();
    }

    private function generateQr(string $data): string
    {
        $png = $this->fetchQrFromApi($data) ?? $this->generateQrLocally($data);

        if ($png === null || $png === '') {
            return '';
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }

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
}
