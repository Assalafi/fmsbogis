<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends BaseModel
{
    public const MANUAL_TYPES = [
        'cashbook_addition',
        'cashbook_deduction',
        'bank_addition',
        'bank_deduction',
    ];

    public const ADJUSTMENT_CATEGORIES = [
        'credit_transfer' => ['type' => 'cashbook_addition', 'label' => 'Credit transfer'],
        'interest_received' => ['type' => 'cashbook_addition', 'label' => 'Interest received'],
        'stale_cheque_reversed' => ['type' => 'cashbook_addition', 'label' => 'Stale cheque reversed'],
        'outstanding_stale_revenue' => ['type' => 'cashbook_addition', 'label' => 'Outstanding stale cheque / revenue'],
        'bank_charge' => ['type' => 'cashbook_deduction', 'label' => 'Bank charge'],
        'debit_transfer' => ['type' => 'cashbook_deduction', 'label' => 'Debit transfer'],
        'items_in_bank_not_cashbook' => ['type' => 'cashbook_addition', 'label' => 'Items in bank not cashbook'],
        'uncredited_lodgement' => ['type' => 'bank_addition', 'label' => 'Uncredited lodgement'],
        'items_in_cashbook_not_bank' => ['type' => 'bank_addition', 'label' => 'Items in cashbook not bank'],
        'unpresented_payment' => ['type' => 'bank_deduction', 'label' => 'Unpresented cheque / payment'],
        'other_cashbook_addition' => ['type' => 'cashbook_addition', 'label' => 'Other cashbook addition'],
        'other_cashbook_deduction' => ['type' => 'cashbook_deduction', 'label' => 'Other cashbook deduction'],
        'other_bank_addition' => ['type' => 'bank_addition', 'label' => 'Other bank balance addition'],
        'other_bank_deduction' => ['type' => 'bank_deduction', 'label' => 'Other bank balance deduction'],
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'amount' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function cashbookEntry(): BelongsTo
    {
        return $this->belongsTo(CashbookEntry::class);
    }

    public function bankStatementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    public function displayType(): string
    {
        if ($this->item_type === 'cashbook_only' && $this->cashbookEntry) {
            return (float) $this->cashbookEntry->receipt_amount > 0
                ? 'Uncredited lodgement'
                : 'Unpresented payment';
        }

        if ($this->item_type === 'bank_only' && $this->bankStatementLine) {
            return $this->bankStatementLine->isCredit()
                ? 'Direct bank credit'
                : 'Bank debit / charge';
        }

        return match ($this->item_type) {
            'matched' => 'Matched transaction',
            'cashbook_addition' => 'Cashbook addition',
            'cashbook_deduction' => 'Cashbook deduction',
            'bank_addition' => 'Bank balance addition',
            'bank_deduction' => 'Bank balance deduction',
            'bank_adjustment' => 'Legacy cashbook adjustment',
            default => ucfirst(str_replace('_', ' ', $this->item_type)),
        };
    }

    public function effectLabel(): string
    {
        if ($this->item_type === 'matched') {
            return 'No adjustment';
        }

        if ($this->item_type === 'cashbook_only' && $this->cashbookEntry) {
            return (float) $this->cashbookEntry->receipt_amount > 0
                ? 'Add to bank balance'
                : 'Subtract from bank balance';
        }

        if ($this->item_type === 'bank_only' && $this->bankStatementLine) {
            return $this->bankStatementLine->isCredit()
                ? 'Add to cashbook'
                : 'Subtract from cashbook';
        }

        return match ($this->item_type) {
            'cashbook_addition' => 'Add to cashbook',
            'cashbook_deduction' => 'Subtract from cashbook',
            'bank_addition' => 'Add to bank balance',
            'bank_deduction' => 'Subtract from bank balance',
            'bank_adjustment' => Money::isNegative($this->amount) ? 'Subtract from cashbook' : 'Add to cashbook',
            default => 'Review',
        };
    }
}
