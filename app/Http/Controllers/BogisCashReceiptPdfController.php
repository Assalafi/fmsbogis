<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\ReceiptPdfService;
use Barryvdh\DomPDF\Facade\Pdf;

class BogisCashReceiptPdfController extends Controller
{
    public function show(Receipt $receipt)
    {
        $pdf = app(ReceiptPdfService::class)->generate($receipt);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.app(ReceiptPdfService::class)->filename($receipt).'"',
        ]);
    }
}
