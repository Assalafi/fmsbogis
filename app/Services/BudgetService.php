<?php

namespace App\Services;

use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Support\Money;

class BudgetService
{
    /**
     * Total budget available for the code:
     * Original + Supplementary + Virement In - Virement Out.
     */
    public function totalBudget(EconomicCodeBudget $budget): string
    {
        return Money::add(
            $budget->original_budget,
            $budget->supplementary_budget,
            $budget->virement_in,
            Money::sub(0, $budget->virement_out)
        );
    }

    public function paidPayments(EconomicCode $economicCode, FiscalYear $fiscalYear): string
    {
        $sum = Payment::where('economic_code_id', $economicCode->id)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereIn('status', ['paid'])
            ->sum('amount');

        return Money::normalize($sum);
    }

    public function approvedUnpaidPayments(EconomicCode $economicCode, FiscalYear $fiscalYear): string
    {
        $sum = Payment::where('economic_code_id', $economicCode->id)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereIn('status', ['approved'])
            ->sum('amount');

        return Money::normalize($sum);
    }

    public function availableBudget(EconomicCode $economicCode, FiscalYear $fiscalYear): string
    {
        $budget = EconomicCodeBudget::where('economic_code_id', $economicCode->id)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->first();

        if (! $budget || $budget->status !== 'approved') {
            return '0.00';
        }

        $total = $this->totalBudget($budget);
        $paid = $this->paidPayments($economicCode, $fiscalYear);
        $approvedUnpaid = $this->approvedUnpaidPayments($economicCode, $fiscalYear);

        $available = Money::sub($total, Money::add($paid, $approvedUnpaid));

        return Money::compare($available, 0) === -1 ? '0.00' : $available;
    }

    public function canPay(EconomicCode $economicCode, FiscalYear $fiscalYear, string $amount): bool
    {
        return Money::compare($amount, $this->availableBudget($economicCode, $fiscalYear)) <= 0;
    }
}
