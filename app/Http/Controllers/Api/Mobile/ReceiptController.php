<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * Return receipts for offline synchronisation.
     * Supports incremental sync via the `since` (updated_at) filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Receipt::with(['account', 'economicCode', 'creator', 'approver', 'poster', 'fiscalYear'])
            ->orderByDesc('date_of_transaction');

        if ($request->filled('since')) {
            $query->where('updated_at', '>', $request->since);
        }

        $receipts = $query->limit(20000)->get();

        return response()->json([
            'code' => '00',
            'count' => $receipts->count(),
            'data' => $receipts->map(fn (Receipt $r) => $this->payload($r))->values(),
        ]);
    }

    /**
     * Verify a single receipt by its receipt number, treasury voucher
     * number or UUID. This is what the QR scanner calls.
     */
    public function show(Request $request, string $receiptNo): JsonResponse
    {
        $receipt = Receipt::with(['account', 'economicCode', 'creator', 'approver', 'poster', 'fiscalYear'])
            ->where('receipt_number', $receiptNo)
            ->orWhere('treasury_receipt_voucher_number', $receiptNo)
            ->orWhere('id', $receiptNo)
            ->first();

        if (! $receipt) {
            return response()->json([
                'code' => '404',
                'message' => 'Receipt not found. The receipt number is invalid or has not been issued.',
            ], 404);
        }

        return response()->json([
            'code' => '00',
            'data' => $this->payload($receipt),
        ]);
    }

    protected function payload(Receipt $r): array
    {
        $valid = in_array($r->status, ['posted', 'approved'], true);

        return [
            'id' => $r->id,
            'receipt_number' => $r->receipt_number,
            'treasury_receipt_voucher_number' => $r->treasury_receipt_voucher_number,
            'external_reference' => $r->external_reference,
            'date_of_transaction' => $r->date_of_transaction?->toDateString(),
            'amount' => (float) $r->amount,
            'from_whom_received_to_whom_paid' => $r->from_whom_received_to_whom_paid,
            'payer_phone' => $r->payer_phone,
            'payer_email' => $r->payer_email,
            'station' => $r->station,
            'head_no' => $r->head_no,
            'sub_head_no' => $r->sub_head_no,
            'details' => $r->details,
            'payment_method' => $r->payment_method,
            'expenditure_credits' => $r->expenditure_credits,
            'bank_credit_slip_cheque_mandate_number' => $r->bank_credit_slip_cheque_mandate_number,
            'status' => $r->status,
            'is_valid' => $valid,
            'account' => [
                'name' => $r->account?->account_name,
                'type' => $r->account?->account_type,
                'bank' => $r->account?->bank_name,
                'number' => $r->account?->account_number,
            ],
            'economic_code' => [
                'code' => $r->economicCode?->code,
                'name' => $r->economicCode?->name,
                'account_type' => $r->economicCode?->account_type,
            ],
            'fiscal_year' => $r->fiscalYear?->name,
            'collector' => ($r->approver ?? $r->creator)?->name,
            'posted_by' => $r->poster?->name,
            'approved_at' => $r->approved_at?->toIso8601String(),
            'posted_at' => $r->posted_at?->toIso8601String(),
            'reversed_at' => $r->reversed_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }
}
