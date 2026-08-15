<?php

namespace App\Services;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\FiscalYear;
use App\Models\Receipt;
use App\Models\Setting;
use App\Support\ActiveFiscalYear;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExternalReceiptService
{
    public function __construct(
        private ReceiptService $receiptService,
        private AuditService $auditService,
    ) {
    }

    /**
     * Create (or return) a receipt from an external system payment.
     *
     * @param  array{
     *     payment_reference: string,
     *     fee_type: string,
     *     amount: float|string,
     *     customer_name?: string|null,
     *     customer_email?: string|null,
     *     customer_phone?: string|null,
     *     channel?: string|null,
     *     paid_at?: string|null,
     *     virtual_account_number?: string|null
     * }  $payment
     * @return array{receipt: Receipt, created: bool}
     */
    public function import(array $payment): array
    {
        $reference = trim((string) ($payment['payment_reference'] ?? ''));

        if ($reference === '') {
            throw new \InvalidArgumentException('payment_reference is required.');
        }

        $existing = Receipt::where('external_reference', $reference)
            ->orWhere('treasury_receipt_voucher_number', $reference)
            ->first();

        if ($existing) {
            return ['receipt' => $existing, 'created' => false];
        }

        $account = $this->resolveAccount();
        $economicCode = $this->resolveEconomicCode((string) ($payment['fee_type'] ?? ''));
        $fiscalYear = $this->resolveFiscalYear($payment['paid_at'] ?? null);

        $amount = Money::normalize((float) ($payment['amount'] ?? 0));

        if (Money::compare($amount, 0) <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (! $account || ! $economicCode || ! $fiscalYear) {
            throw new \RuntimeException(
                'BOGIS Finance is not configured for external receipts: missing account, revenue economic code or fiscal year.'
            );
        }

        $receipt = DB::transaction(function () use ($payment, $reference, $account, $economicCode, $fiscalYear, $amount) {
            $receipt = Receipt::create([
                'account_id' => $account->id,
                'economic_code_id' => $economicCode->id,
                'fiscal_year_id' => $fiscalYear->id,
                'date_of_transaction' => $payment['paid_at'] ?? today()->toDateString(),
                'amount' => $amount,
                'treasury_receipt_voucher_number' => $reference,
                'receipt_number' => $reference,
                'external_reference' => $reference,
                'external_source' => 'bogis-forms',
                'from_whom_received_to_whom_paid' => $payment['customer_name'] ?? null,
                'payer_phone' => $payment['customer_phone'] ?? null,
                'payment_method' => 'bank',
                'details' => $this->buildDetails($payment),
                'status' => 'pending',
                'created_by' => null,
            ]);

            $receipt->update([
                'status' => 'approved',
                'approved_by' => null,
                'approved_at' => now(),
            ]);

            $this->receiptService->post($receipt);

            $this->auditService->log('Receipt Imported From BOGIS Forms', $receipt, null, [
                'external_reference' => $reference,
                'amount' => $amount,
            ]);

            return $receipt;
        });

        return ['receipt' => $receipt, 'created' => true];
    }

    protected function resolveAccount(): ?Account
    {
        $id = Setting::get('external_receipt_account_id');

        if ($id && $account = Account::find($id)) {
            return $account;
        }

        return Account::active()->orderByRaw("CASE account_type WHEN 'overhead' THEN 0 ELSE 1 END")->orderBy('account_name')->first();
    }

    protected function resolveEconomicCode(string $feeType): ?EconomicCode
    {
        $isPremium = str_starts_with($feeType, 'Allocation Fee') || $feeType === 'Plot Premium';

        $key = $isPremium ? 'external_premium_code_id' : 'external_application_fee_code_id';

        $id = Setting::get($key);

        if ($id && $code = EconomicCode::find($id)) {
            return $code;
        }

        return EconomicCode::revenue()->active()->orderBy('code')->first();
    }

    protected function resolveFiscalYear(?string $paidAt): ?FiscalYear
    {
        if ($paidAt) {
            $date = now()->parse($paidAt);

            $year = FiscalYear::where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->first();

            if ($year) {
                return $year;
            }
        }

        return ActiveFiscalYear::get();
    }

    protected function buildDetails(array $payment): string
    {
        return trim((string) ($payment['fee_type'] ?? 'BOGIS Forms Payment'));
    }

    /**
     * Pull all paid payments from BOGIS Forms and create missing receipts.
     *
     * @return array{created: int, existing: int, failed: int}
     */
    public function syncFromForms(?string $since = null): array
    {
        $baseUrl = rtrim((string) config('services.bogis_forms.api_url'), '/');
        $token = (string) config('services.bogis_forms.api_token');

        if ($baseUrl === '' || $token === '') {
            throw new \RuntimeException('BOGIS_FORMS_API_URL and BOGIS_FORMS_API_TOKEN must be configured in .env.');
        }

        $created = 0;
        $existing = 0;
        $failed = 0;
        $page = 1;

        do {
            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get("{$baseUrl}/api/paid-payments", [
                    'page' => $page,
                    'since' => $since,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('BOGIS Forms API request failed: '.$response->status().' '.$response->body());
            }

            $json = $response->json();
            $payments = $json['data'] ?? [];

            foreach ($payments as $payment) {
                try {
                    $result = $this->import($payment);

                    $result['created'] ? $created++ : $existing++;
                } catch (\Throwable) {
                    $failed++;
                }
            }

            $page++;

            if (empty($payments) || empty($json['next_page_url'])) {
                break;
            }
        } while (true);

        return ['created' => $created, 'existing' => $existing, 'failed' => $failed];
    }
}
