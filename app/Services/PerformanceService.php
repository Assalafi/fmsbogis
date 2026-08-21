<?php

namespace App\Services;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\EconomicCodeBudget;
use App\Models\FiscalYear;
use App\Models\Payment;
use App\Models\Receipt;
use App\Support\Money;

class PerformanceService
{
    public function __construct(private BudgetService $budgetService)
    {
    }

    public function totals(FiscalYear $fiscalYear): array
    {
        $approvedBudgets = EconomicCodeBudget::where('fiscal_year_id', $fiscalYear->id)->where('status', 'approved')->get();

        $original = Money::normalize($approvedBudgets->sum('original_budget'));

        $totalBudget = Money::normalize($approvedBudgets->sum(function (EconomicCodeBudget $budget) {
            return (float) $this->budgetService->totalBudget($budget);
        }));

        $receipts = Money::normalize(Receipt::where('fiscal_year_id', $fiscalYear->id)->where('status', 'posted')->sum('amount'));
        $payments = Money::normalize(Payment::where('fiscal_year_id', $fiscalYear->id)->where('status', 'paid')->sum('amount'));

        $capitalPayments = Money::normalize(Payment::where('fiscal_year_id', $fiscalYear->id)->where('status', 'paid')
            ->whereHas('economicCode', fn ($q) => $q->where('account_type', 'capital'))
            ->sum('amount'));

        $overheadPayments = Money::normalize(Payment::where('fiscal_year_id', $fiscalYear->id)->where('status', 'paid')
            ->whereHas('economicCode', fn ($q) => $q->where('account_type', 'overhead'))
            ->sum('amount'));

        $personnelPayments = Money::normalize(Payment::where('fiscal_year_id', $fiscalYear->id)->where('status', 'paid')
            ->whereHas('economicCode', fn ($q) => $q->where('account_type', 'personnel'))
            ->sum('amount'));

        $cashbookBalance = Money::normalize(Account::all()->sum(fn (Account $account) => resolve(CashbookService::class)->closingBalance($account, $fiscalYear)));

        $approvedUnpaid = Money::normalize(Payment::where('fiscal_year_id', $fiscalYear->id)->where('status', 'approved')->sum('amount'));

        return [
            'original_budget' => $original,
            'total_budget' => $totalBudget,
            'total_receipts' => $receipts,
            'total_payments' => $payments,
            'available_budget' => Money::sub($totalBudget, Money::add($payments, $approvedUnpaid)),
            'capital_payments' => $capitalPayments,
            'overhead_payments' => $overheadPayments,
            'personnel_payments' => $personnelPayments,
            'cashbook_balance' => $cashbookBalance,
        ];
    }

    public function economicCodePerformance(FiscalYear $fiscalYear): array
    {
        return EconomicCode::with('budgets')
            ->orderBy('code')
            ->get()
            ->map(function (EconomicCode $code) use ($fiscalYear) {
                $budget = $code->budgets->firstWhere('fiscal_year_id', $fiscalYear->id);

                $receipts = Money::normalize(Receipt::where('economic_code_id', $code->id)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->where('status', 'posted')
                    ->sum('amount'));

                $payments = Money::normalize(Payment::where('economic_code_id', $code->id)
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->where('status', 'paid')
                    ->sum('amount'));

                if ($code->isExpense() && $budget && $budget->status === 'approved') {
                    $total = $this->budgetService->totalBudget($budget);
                    $available = $this->budgetService->availableBudget($code, $fiscalYear);
                    $performance = Money::isZero($total) ? 0 : round((float) Money::div($payments, $total) * 100, 2);
                } else {
                    $total = '0.00';
                    $available = '0.00';
                    $performance = null;
                }

                return [
                    'code' => $code,
                    'budget' => $budget,
                    'original_budget' => $budget?->original_budget ?? '0.00',
                    'total_budget' => $total,
                    'receipts' => $receipts,
                    'payments' => $payments,
                    'available' => $available,
                    'performance' => $performance,
                ];
            })
            ->all();
    }

    public function monthlySeries(FiscalYear $fiscalYear): array
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $receipts = Receipt::where('fiscal_year_id', $fiscalYear->id)
                ->where('status', 'posted')
                ->whereMonth('date_of_transaction', $m)
                ->whereYear('date_of_transaction', $fiscalYear->start_date->year)
                ->sum('amount');

            $payments = Payment::where('fiscal_year_id', $fiscalYear->id)
                ->where('status', 'paid')
                ->whereMonth('date_of_transaction', $m)
                ->whereYear('date_of_transaction', $fiscalYear->start_date->year)
                ->sum('amount');

            $months[] = [
                'label' => \Carbon\Carbon::create()->month($m)->format('M'),
                'receipts' => (float) $receipts,
                'payments' => (float) $payments,
            ];
        }

        return $months;
    }
}
