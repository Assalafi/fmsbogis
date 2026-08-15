<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\CashbookEntry;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    public function __construct(
        private CashbookService $cashbookService,
        private AuditService $auditService,
    ) {
    }

    public function autoMatch(BankReconciliation $reconciliation): void
    {
        $statement = $reconciliation->bankStatement;
        $account = $reconciliation->account;

        $cashbookEntries = CashbookEntry::where('account_id', $account->id)
            ->whereBetween('date', [$statement->statement_from, $statement->statement_to])
            ->where('transaction_type', '!=', 'opening_balance')
            ->get();

        $lines = BankStatementLine::where('bank_statement_id', $statement->id)
            ->where('match_status', 'unmatched')
            ->get();

        $usedEntryIds = [];
        $usedLineIds = [];

        foreach ($cashbookEntries as $entry) {
            $match = null;

            $match = $lines->first(function (BankStatementLine $line) use ($entry, $usedLineIds) {
                if (in_array($line->id, $usedLineIds, true)) {
                    return false;
                }

                if ($entry->reference && strtolower(trim((string) $line->reference)) === strtolower(trim((string) $entry->reference))) {
                    return Money::compare($line->amount(), $this->entryAmount($entry)) === 0;
                }

                return false;
            });

            if (! $match) {
                $match = $lines->first(function (BankStatementLine $line) use ($entry, $usedLineIds) {
                    if (in_array($line->id, $usedLineIds, true)) {
                        return false;
                    }

                    $dateTolerance = abs($line->date_of_transaction->diffInDays($entry->date)) <= 3;

                    return Money::compare($line->amount(), $this->entryAmount($entry)) === 0 && $dateTolerance;
                });
            }

            if ($match) {
                $this->createMatchedItem($reconciliation, $entry, $match);
                $usedEntryIds[] = $entry->id;
                $usedLineIds[] = $match->id;
            }
        }

        foreach ($lines as $line) {
            if (! in_array($line->id, $usedLineIds, true)) {
                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_statement_line_id' => $line->id,
                    'item_type' => 'bank_only',
                    'amount' => $line->amount(),
                    'notes' => $line->description,
                ]);
                $line->update(['match_status' => 'bank_only']);
            }
        }

        foreach ($cashbookEntries as $entry) {
            if (! in_array($entry->id, $usedEntryIds, true)) {
                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'cashbook_entry_id' => $entry->id,
                    'item_type' => 'cashbook_only',
                    'amount' => $this->entryAmount($entry),
                    'notes' => $entry->details,
                ]);
            }
        }

        $this->recalculate($reconciliation);
    }

    protected function entryAmount(CashbookEntry $entry): string
    {
        return Money::compare($entry->receipt_amount, 0) === 1
            ? $entry->receipt_amount
            : $entry->payment_amount;
    }

    protected function createMatchedItem(BankReconciliation $reconciliation, CashbookEntry $entry, BankStatementLine $line): void
    {
        BankReconciliationItem::create([
            'bank_reconciliation_id' => $reconciliation->id,
            'cashbook_entry_id' => $entry->id,
            'bank_statement_line_id' => $line->id,
            'item_type' => 'matched',
            'amount' => $this->entryAmount($entry),
        ]);

        $line->update(['match_status' => 'matched']);
    }

    public function recalculate(BankReconciliation $reconciliation): void
    {
        $account = $reconciliation->account;
        $statement = $reconciliation->bankStatement;

        $cashbookBalance = $this->cashbookService->closingBalance($account);
        $bankBalance = $statement->closing_balance;

        $bankOnly = Money::normalize($reconciliation->items()->where('item_type', 'bank_only')->sum('amount'));
        $cashbookOnly = Money::normalize($reconciliation->items()->where('item_type', 'cashbook_only')->sum('amount'));
        $adjustments = Money::normalize($reconciliation->items()->where('item_type', 'bank_adjustment')->sum('amount'));

        $adjustedCashbook = Money::add($cashbookBalance, $cashbookOnly, $adjustments);
        $adjustedBank = Money::add($bankBalance, $bankOnly);

        $reconciliation->update([
            'cashbook_balance' => $cashbookBalance,
            'bank_statement_balance' => $bankBalance,
            'adjusted_cashbook_balance' => $adjustedCashbook,
            'adjusted_bank_balance' => $adjustedBank,
            'difference' => Money::sub($adjustedCashbook, $adjustedBank),
        ]);
    }

    public function approve(BankReconciliation $reconciliation): void
    {
        DB::transaction(function () use ($reconciliation) {
            $this->recalculate($reconciliation);

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
        return DB::transaction(function () use ($account, $statement) {
            $reconciliation = BankReconciliation::create([
                'account_id' => $account->id,
                'bank_statement_id' => $statement->id,
                'reconciliation_date' => today(),
                'status' => 'draft',
                'prepared_by' => auth()->id(),
            ]);

            $this->autoMatch($reconciliation);

            $this->auditService->log('Reconciliation Created', $reconciliation);

            return $reconciliation;
        });
    }
}
