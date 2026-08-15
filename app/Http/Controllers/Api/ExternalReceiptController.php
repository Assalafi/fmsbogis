<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExternalReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
