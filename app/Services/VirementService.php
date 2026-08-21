<?php

namespace App\Services;

use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\Virement;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class VirementService
{
    public function __construct(
        private BudgetService $budgetService,
        private AuditService $auditService,
    ) {
    }

    public function validate(
        FiscalYear $fiscalYear,
        EconomicCode $from,
        EconomicCode $to,
        string $amount
    ): ?string {
        if ($from->id === $to->id) {
            return 'Source Economic Code cannot be the same as Destination Economic Code.';
        }

        if (! $from->isExpense() || ! $to->isExpense()) {
            return 'Both Economic Codes must be Expense codes.';
        }

        if (! $fiscalYear->isOpen()) {
            return 'The selected Fiscal Year is closed.';
        }

        $sourceBudget = EconomicCodeBudget::where('fiscal_year_id', $fiscalYear->id)
            ->where('economic_code_id', $from->id)
            ->first();

        $destinationBudget = EconomicCodeBudget::where('fiscal_year_id', $fiscalYear->id)
            ->where('economic_code_id', $to->id)
            ->first();

        if (! $sourceBudget || $sourceBudget->status !== 'approved') {
            return 'Source Economic Code has no Approved Budget.';
        }

        if (! $destinationBudget || $destinationBudget->status !== 'approved') {
            return 'Destination Economic Code has no Approved Budget.';
        }

        if (Money::compare($amount, 0) <= 0) {
            return 'Virement amount must be greater than zero.';
        }

        if ($from->account_type !== $to->account_type && ! auth()->user()->can('virements.cross_type')) {
            return 'Cross-type virement ('.ucfirst($from->account_type).' to '.ucfirst($to->account_type).') is not allowed.';
        }

        $available = $this->budgetService->availableBudget($from, $fiscalYear);
        if (Money::compare($amount, $available) === 1) {
            return 'Source Available Budget (₦'.Money::format($available).') is less than the virement amount.';
        }

        return null;
    }

    public function approve(Virement $virement): void
    {
        DB::transaction(function () use ($virement) {
            $virement->refresh();

            $error = $this->validate(
                $virement->fiscalYear,
                $virement->fromEconomicCode,
                $virement->toEconomicCode,
                $virement->amount
            );
            if ($error) {
                throw new \DomainException($error);
            }

            $sourceBudget = EconomicCodeBudget::where('fiscal_year_id', $virement->fiscal_year_id)
                ->where('economic_code_id', $virement->from_economic_code_id)
                ->lockForUpdate()
                ->first();

            $destinationBudget = EconomicCodeBudget::where('fiscal_year_id', $virement->fiscal_year_id)
                ->where('economic_code_id', $virement->to_economic_code_id)
                ->lockForUpdate()
                ->first();

            $sourceBudget->update(['virement_out' => Money::add($sourceBudget->virement_out, $virement->amount)]);
            $destinationBudget->update(['virement_in' => Money::add($destinationBudget->virement_in, $virement->amount)]);

            $virement->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->auditService->log('Virement Approved', $virement, ['status' => 'pending'], [
                'status' => 'approved',
                'amount' => $virement->amount,
                'from' => $virement->fromEconomicCode->code,
                'to' => $virement->toEconomicCode->code,
            ]);
        });
    }
}
