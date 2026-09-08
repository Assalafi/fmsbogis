<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankStatement;
use App\Models\CashbookEntry;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    public function __construct(
        private AuditService $auditService,
    ) {}

    public function recalculate(BankReconciliation $reconciliation): void
    {
        $statement = $reconciliation->bankStatement;
        $cashbookBalance = $this->cashbookBalanceAsAt($reconciliation->account, $statement->statement_to);
        $bankBalance = $statement->closing_balance;
        $breakdown = $this->breakdown($reconciliation);

        $adjustedCashbook = Money::add(
            $cashbookBalance,
            $breakdown['cashbook_additions'],
            Money::sub(0, $breakdown['cashbook_deductions']),
        );
        $adjustedBank = Money::add(
            $bankBalance,
            $breakdown['bank_additions'],
            Money::sub(0, $breakdown['bank_deductions']),
        );

        $reconciliation->update([
            'cashbook_balance' => $cashbookBalance,
            'bank_statement_balance' => $bankBalance,
            'adjusted_cashbook_balance' => $adjustedCashbook,
            'adjusted_bank_balance' => $adjustedBank,
            'difference' => Money::sub($adjustedCashbook, $adjustedBank),
        ]);
    }

    public function breakdown(BankReconciliation $reconciliation): array
    {
        // Always reload the items so a recalculation immediately reflects a
        // classification that was added or removed in the current request.
        $reconciliation->load(['items.cashbookEntry', 'items.bankStatementLine']);

        $totals = [
            'cashbook_additions' => '0.00',
            'cashbook_deductions' => '0.00',
            'bank_additions' => '0.00',
            'bank_deductions' => '0.00',
        ];
        $manualTypeKeys = [
            'cashbook_addition' => 'cashbook_additions',
            'cashbook_deduction' => 'cashbook_deductions',
            'bank_addition' => 'bank_additions',
            'bank_deduction' => 'bank_deductions',
        ];

        foreach ($reconciliation->items as $item) {
            $amount = Money::normalize($item->amount);

            if ($item->item_type === 'bank_only' && $item->bankStatementLine) {
                $key = $item->bankStatementLine->isCredit() ? 'cashbook_additions' : 'cashbook_deductions';
                $totals[$key] = Money::add($totals[$key], $amount);
            } elseif ($item->item_type === 'cashbook_only' && $item->cashbookEntry) {
                $key = Money::compare($item->cashbookEntry->receipt_amount, 0) === 1
                    ? 'bank_additions'
                    : 'bank_deductions';
                $totals[$key] = Money::add($totals[$key], $amount);
            } elseif (isset($manualTypeKeys[$item->item_type])) {
                $key = $manualTypeKeys[$item->item_type];
                $totals[$key] = Money::add($totals[$key], $amount);
            } elseif ($item->item_type === 'bank_adjustment') {
                if (Money::isNegative($amount)) {
                    $totals['cashbook_deductions'] = Money::add(
                        $totals['cashbook_deductions'],
                        ltrim($amount, '-'),
                    );
                } else {
                    $totals['cashbook_additions'] = Money::add($totals['cashbook_additions'], $amount);
                }
            }
        }

        return $totals;
    }

    public function cashbookBalanceAsAt(Account $account, mixed $date): string
    {
        $query = CashbookEntry::where('account_id', $account->id)
            ->whereDate('date', '<=', $date);

        return Money::add(
            $account->opening_balance,
            $query->clone()->sum('receipt_amount'),
            Money::sub(0, $query->clone()->sum('payment_amount')),
        );
    }

    public function approve(BankReconciliation $reconciliation): void
    {
        DB::transaction(function () use ($reconciliation): void {
            $this->recalculate($reconciliation);
            $reconciliation->refresh();

            if (! Money::isZero($reconciliation->difference)) {
                throw new \DomainException('Reconciliation cannot be approved. Difference is ₦'.Money::format($reconciliation->difference).'.');
            }

            $reconciliation->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $reconciliation->bankStatement->update(['status' => 'reconciled']);

            $this->auditService->log('Reconciliation Approved', $reconciliation, ['status' => 'draft'], ['status' => 'approved']);
        });
    }

    public function createFor(Account $account, BankStatement $statement): BankReconciliation
    {
        if ($statement->account_id !== $account->id) {
            throw new \DomainException('The selected bank statement does not belong to this account.');
        }

        if ($statement->status === 'reconciled' || $statement->reconciliations()->exists()) {
            throw new \DomainException('This bank statement already has a reconciliation.');
        }

        return DB::transaction(function () use ($account, $statement) {
            $reconciliation = BankReconciliation::create([
                'account_id' => $account->id,
                'bank_statement_id' => $statement->id,
                'reconciliation_date' => $statement->statement_to,
                'status' => 'draft',
                'prepared_by' => auth()->id(),
            ]);

            $this->recalculate($reconciliation);
            $this->auditService->log('Reconciliation Created', $reconciliation);

            return $reconciliation;
        });
    }
}
