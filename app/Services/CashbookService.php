<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CashbookEntry;
use App\Models\FiscalYear;
use App\Models\Receipt;
use App\Models\Payment;
use App\Support\ActiveFiscalYear;
use App\Support\Money;

class CashbookService
{
    public function generateForReceipt(Receipt $receipt): CashbookEntry
    {
        $account = $receipt->account;

        return CashbookEntry::create([
            'account_id' => $account->id,
            'economic_code_id' => $receipt->economic_code_id,
            'fiscal_year_id' => $receipt->fiscal_year_id,
            'transaction_type' => 'receipt',
            'transaction_id' => $receipt->id,
            'date' => $receipt->date_of_transaction,
            'reference' => $receipt->treasury_receipt_voucher_number,
            'details' => $receipt->details,
            'receipt_amount' => $receipt->amount,
            'payment_amount' => 0,
        ]);
    }

    public function generateForPayment(Payment $payment): CashbookEntry
    {
        return CashbookEntry::create([
            'account_id' => $payment->account_id,
            'economic_code_id' => $payment->economic_code_id,
            'fiscal_year_id' => $payment->fiscal_year_id,
            'transaction_type' => 'payment',
            'transaction_id' => $payment->id,
            'date' => $payment->date_of_transaction,
            'reference' => $payment->treasury_receipt_voucher_number,
            'details' => $payment->details,
            'receipt_amount' => 0,
            'payment_amount' => $payment->amount,
        ]);
    }

    public function generateOpeningBalance(Account $account, FiscalYear $fiscalYear): CashbookEntry
    {
        return CashbookEntry::firstOrCreate(
            ['transaction_type' => 'opening_balance', 'transaction_id' => $account->id],
            [
                'account_id' => $account->id,
                'fiscal_year_id' => $fiscalYear->id,
                'transaction_type' => 'opening_balance',
                'transaction_id' => $account->id,
                'date' => $fiscalYear->start_date,
                'reference' => 'Opening Balance',
                'details' => 'Opening balance for '.$fiscalYear->name,
                'receipt_amount' => $account->opening_balance,
                'payment_amount' => 0,
            ]
        );
    }

    public function closingBalance(Account $account, ?FiscalYear $fiscalYear = null): string
    {
        $fiscalYear = $fiscalYear ?? ActiveFiscalYear::get();

        $query = CashbookEntry::where('account_id', $account->id);

        if ($fiscalYear) {
            $query->where('fiscal_year_id', $fiscalYear->id);
        }

        $receipts = $query->clone()->sum('receipt_amount');
        $payments = $query->clone()->sum('payment_amount');

        return Money::add($account->opening_balance, Money::sub($receipts, $payments));
    }

    public function rebuildRunningBalances(Account $account, ?FiscalYear $fiscalYear = null): void
    {
        $fiscalYear = $fiscalYear ?? ActiveFiscalYear::get();

        $entries = CashbookEntry::where('account_id', $account->id)
            ->when($fiscalYear, fn ($q) => $q->where('fiscal_year_id', $fiscalYear->id))
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $balance = Money::normalize($account->opening_balance);
        foreach ($entries as $entry) {
            $balance = Money::add($balance, $entry->receipt_amount, Money::sub(0, $entry->payment_amount));
            $entry->update(['running_balance' => $balance]);
        }
    }
}
