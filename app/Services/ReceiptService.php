<?php

namespace App\Services;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Receipt;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    public function __construct(
        private CashbookService $cashbookService,
        private AuditService $auditService,
    ) {
    }

    public function validate(EconomicCode $economicCode, Account $account): ?string
    {
        if (! $economicCode->isRevenue()) {
            return 'The selected Economic Code is not a Revenue code. Receipts require Revenue Economic Codes.';
        }

        if (! $economicCode->isActive()) {
            return 'The selected Economic Code is inactive.';
        }

        if (! $account->isActive()) {
            return 'The selected Account is inactive.';
        }

        return null;
    }

    public function post(Receipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            if ($receipt->status === 'posted') {
                return;
            }

            $receipt->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            $this->cashbookService->generateForReceipt($receipt);
            $this->cashbookService->rebuildRunningBalances($receipt->account, $receipt->fiscalYear);

            $this->auditService->log('Receipt Posted', $receipt, null, [
                'treasury_receipt_voucher_number' => $receipt->treasury_receipt_voucher_number,
                'amount' => $receipt->amount,
            ]);
        });
    }

    public function reverse(Receipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $receipt->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);

            $entry = $receipt->cashbookEntry;
            if ($entry) {
                $entry->update([
                    'receipt_amount' => 0,
                    'payment_amount' => $entry->receipt_amount,
                    'details' => $entry->details.' (Reversal of '.$receipt->treasury_receipt_voucher_number.')',
                ]);
                $this->cashbookService->rebuildRunningBalances($receipt->account, $receipt->fiscalYear);
            }

            $this->auditService->log('Receipt Reversed', $receipt, [
                'status' => 'posted',
            ], [
                'status' => 'reversed',
            ]);
        });
    }

    public function totalsForPeriod(FiscalYear $fiscalYear, ?Account $account = null): array
    {
        $query = Receipt::where('fiscal_year_id', $fiscalYear->id)
            ->where('status', 'posted')
            ->when($account, fn ($q) => $q->where('account_id', $account->id));

        return [
            'today' => Money::normalize((clone $query)->whereDate('date_of_transaction', today())->sum('amount')),
            'month' => Money::normalize((clone $query)->whereMonth('date_of_transaction', now()->month)->whereYear('date_of_transaction', now()->year)->sum('amount')),
            'year_to_date' => Money::normalize((clone $query)->sum('amount')),
            'count' => (clone $query)->count(),
        ];
    }
}
