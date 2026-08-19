<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\ExternalReceiptService;
use App\Services\ReceiptPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ExternalReceiptController extends Controller
{
    /**
     * Accept a paid payment from BOGIS Forms and create/post a receipt.
     */
    public function store(Request $request, ExternalReceiptService $service): JsonResponse
    {
        $data = $request->validate([
            'payment_reference' => ['required', 'string', 'max:255'],
            'fee_type' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'channel' => ['nullable', 'string', 'max:20'],
            'paid_at' => ['nullable', 'date'],
            'virtual_account_number' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $result = $service->import($data);

            return response()->json([
                'code' => $result['created'] ? '201' : '200',
                'message' => $result['created'] ? 'Receipt created and posted.' : 'Receipt already exists.',
                'data' => [
                    'receipt_id' => $result['receipt']->id,
                    'treasury_receipt_voucher_number' => $result['receipt']->treasury_receipt_voucher_number,
                    'status' => $result['receipt']->status,
                    'url' => route('receipts.show', $result['receipt']),
                ],
            ], $result['created'] ? 201 : 200);
        } catch (\Throwable $e) {
            Log::error('External receipt import failed', [
                'error' => $e->getMessage(),
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            return response()->json([
                'code' => '500',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List receipts by payment references or payer email.
     * The caller (BOGIS Forms) only passes references owned by the
     * authenticated applicant, so each user can only see their own receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'references' => ['nullable', 'array', 'max:500'],
            'references.*' => ['string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $query = Receipt::whereNotNull('external_reference');

        if ($request->filled('references')) {
            $query->whereIn('external_reference', array_values($request->input('references')));
        } elseif ($request->filled('email')) {
            $query->where('payer_email', $request->email);
        } else {
            return response()->json(['code' => '400', 'data' => []], 400);
        }

        $receipts = $query->orderBy('date_of_transaction')->get();

        return response()->json([
            'code' => '00',
            'data' => $receipts->map(fn (Receipt $r): array => [
                'reference' => $r->external_reference,
                'treasury_receipt_voucher_number' => $r->treasury_receipt_voucher_number,
                'amount' => (float) $r->amount,
                'date_of_transaction' => $r->date_of_transaction->toDateString(),
                'from_whom_received_to_whom_paid' => $r->from_whom_received_to_whom_paid,
                'status' => $r->status,
            ])->values(),
        ]);
    }

    /**
     * Stream a single receipt PDF by its external payment reference.
     */
    public function pdf(Request $request, string $reference): Response
    {
        $receipt = Receipt::where('external_reference', $reference)->first();

        abort_unless($receipt, 404, 'Receipt not found.');

        $pdf = app(ReceiptPdfService::class)->generate($receipt);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="BOGIS-Cash-Receipt-'.$receipt->treasury_receipt_voucher_number.'.pdf"',
        ]);
    }
}
