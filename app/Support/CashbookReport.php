<?php

namespace App\Support;

use App\Models\CashbookEntry;
use Illuminate\Support\Collection;

class CashbookReport
{
    public static function rows(Collection $entries): Collection
    {
        $debits = $entries
            ->filter(fn (CashbookEntry $entry) => (float) $entry->receipt_amount > 0)
            ->values()
            ->map(fn (CashbookEntry $entry) => self::debitRow($entry));

        $credits = $entries
            ->filter(fn (CashbookEntry $entry) => (float) $entry->payment_amount > 0)
            ->values()
            ->map(fn (CashbookEntry $entry) => self::creditRow($entry));

        $rowCount = max($debits->count(), $credits->count());

        return collect(range(0, max(0, $rowCount - 1)))
            ->take($rowCount)
            ->map(fn (int $index) => [
                'debit' => $debits->get($index),
                'credit' => $credits->get($index),
            ]);
    }

    private static function debitRow(CashbookEntry $entry): array
    {
        $source = self::source($entry);
        $isReceipt = $entry->transaction_type === 'receipt';
        $method = $source?->payment_method;
        $amount = (float) $entry->receipt_amount;

        return [
            'date' => $entry->date,
            'treasury_receipt_number' => $isReceipt ? ($source?->treasury_receipt_voucher_number ?? $entry->reference) : null,
            'bank_credit_slip_number' => $source?->bank_credit_slip_cheque_mandate_number,
            'from_whom_received' => $source?->from_whom_received_to_whom_paid ?: $entry->details,
            'treasury_voucher_number' => $isReceipt ? null : ($source?->treasury_receipt_voucher_number ?? $entry->reference),
            'expenditure_credits' => $isReceipt ? $source?->expenditure_credits : 'Payment reversal',
            'economic_code' => $entry->economicCode?->code,
            'gross' => $amount,
            'cash' => $method === 'cash' ? $amount : null,
            'bank' => $method === 'bank' ? $amount : null,
        ];
    }

    private static function creditRow(CashbookEntry $entry): array
    {
        $source = self::source($entry);
        $isPayment = $entry->transaction_type === 'payment';
        $method = $source?->payment_method;
        $amount = (float) $entry->payment_amount;

        return [
            'date' => $entry->date,
            'to_whom_paid' => $source?->from_whom_received_to_whom_paid ?: $entry->details,
            'dept_voucher_number' => $isPayment ? $source?->dept_voucher_number : null,
            'treasury_voucher_number' => $source?->treasury_receipt_voucher_number ?? $entry->reference,
            'cheque_mandate_number' => $source?->bank_credit_slip_cheque_mandate_number,
            'economic_code' => $entry->economicCode?->code,
            'gross' => $amount,
            'cash' => $method === 'cash' ? $amount : null,
            'bank' => $method === 'bank' ? $amount : null,
        ];
    }

    private static function source(CashbookEntry $entry): mixed
    {
        return $entry->transaction_type === 'receipt'
            ? $entry->sourceReceipt
            : $entry->sourcePayment;
    }
}
