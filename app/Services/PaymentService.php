<?php

namespace App\Services;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private BudgetService $budgetService,
        private CashbookService $cashbookService,
        private AuditService $auditService,
    ) {
    }

    public function validate(Account $account, EconomicCode $economicCode, FiscalYear $fiscalYear, string $amount): ?string
    {
        if (! $economicCode->isExpense()) {
            return 'The selected Economic Code is not an Expense code. Payments require Expense Economic Codes.';
        }

        if (! $economicCode->isActive()) {
            return 'The selected Economic Code is inactive.';
        }

        if ($account->account_type !== $economicCode->account_type) {
            return 'The selected Economic Code is a '.ucfirst($economicCode->account_type ?? '').' code and cannot be used with a '.ucfirst($account->account_type).' account.';
        }

        if (! $fiscalYear->isOpen()) {
            return 'The selected Fiscal Year is closed. Payments can only be made in an Open Fiscal Year.';
        }

        $budget = EconomicCodeBudget::where('fiscal_year_id', $fiscalYear->id)
            ->where('economic_code_id', $economicCode->id)
            ->first();

        if (! $budget) {
            return 'No budget exists for Economic Code '.$economicCode->code.' in '.$fiscalYear->name.'.';
        }

        if ($budget->status !== 'approved') {
            return 'The budget for Economic Code '.$economicCode->code.' has not been approved.';
        }

        if (Money::compare($amount, 0) <= 0) {
            return 'Payment amount must be greater than zero.';
        }

        if (! $this->budgetService->canPay($economicCode, $fiscalYear, $amount)) {
            $available = $this->budgetService->availableBudget($economicCode, $fiscalYear);

            return 'Insufficient budget on Economic Code '.$economicCode->code.'. Available balance is ₦'.Money::format($available).'.';
        }

        return null;
    }

    public function approve(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            $error = $this->validate($payment->account, $payment->economicCode, $payment->fiscalYear, $payment->amount);
            if ($error) {
                throw new \DomainException($error);
            }

            $payment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->auditService->log('Payment Approved', $payment, ['status' => 'pending'], ['status' => 'approved']);
        });
    }

    public function markPaid(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->refresh();

            $error = $this->validate($payment->account, $payment->economicCode, $payment->fiscalYear, $payment->amount);
            if ($error) {
                throw new \DomainException($error);
            }

            $payment->update([
                'status' => 'paid',
                'paid_by' => auth()->id(),
                'paid_at' => now(),
            ]);

            $this->cashbookService->generateForPayment($payment);
            $this->cashbookService->rebuildRunningBalances($payment->account, $payment->fiscalYear);

            $this->auditService->log('Payment Paid', $payment, ['status' => $payment->status], [
                'status' => 'paid',
                'treasury_receipt_voucher_number' => $payment->treasury_receipt_voucher_number,
                'amount' => $payment->amount,
            ]);
        });
    }

    public function reject(Payment $payment, string $comment): void
    {
        $payment->update(['status' => 'rejected']);

        $this->auditService->log('Payment Rejected', $payment, ['status' => 'pending'], [
            'status' => 'rejected',
            'comment' => $comment,
        ]);
    }

    public function reverse(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
            ]);

            $entry = $payment->cashbookEntry;
            if ($entry) {
                $entry->update([
                    'payment_amount' => 0,
                    'receipt_amount' => $entry->payment_amount,
                    'details' => $entry->details.' (Reversal of '.$payment->treasury_receipt_voucher_number.')',
                ]);
                $this->cashbookService->rebuildRunningBalances($payment->account, $payment->fiscalYear);
            }

            $this->auditService->log('Payment Reversed', $payment, ['status' => 'paid'], ['status' => 'reversed']);
        });
    }
}
