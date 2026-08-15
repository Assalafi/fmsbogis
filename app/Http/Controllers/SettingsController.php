<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\Setting;
use App\Services\ExternalReceiptService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'accounts' => Account::active()->orderBy('account_name')->get(),
            'revenueCodes' => EconomicCode::revenue()->active()->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'organization_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'receipt_prefix' => ['nullable', 'string', 'max:20'],
            'payment_voucher_prefix' => ['nullable', 'string', 'max:20'],
            'pagination_size' => ['nullable', 'integer', 'min:5', 'max:200'],
            'require_receipt_approval' => ['nullable', 'in:0,1'],
            'require_payment_approval' => ['nullable', 'in:0,1'],
            'allow_cross_type_virement' => ['nullable', 'in:0,1'],
            'external_receipt_account_id' => ['nullable', 'uuid', 'exists:accounts,id'],
            'external_application_fee_code_id' => ['nullable', 'uuid', 'exists:economic_codes,id'],
            'external_premium_code_id' => ['nullable', 'uuid', 'exists:economic_codes,id'],
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with($this->toast('Settings saved.'));
    }

    /**
     * Manually sync already-paid payments from BOGIS Forms into receipts.
     */
    public function syncFormsPayments(Request $request, ExternalReceiptService $service)
    {
        $data = $request->validate([
            'since' => ['nullable', 'date'],
        ]);

        try {
            $result = $service->syncFromForms($data['since'] ?? null);
        } catch (\Throwable $e) {
            return back()->with($this->toast($e->getMessage(), 'danger'));
        }

        $message = "Sync complete. Created: {$result['created']}, already existing: {$result['existing']}, failed: {$result['failed']}.";

        return back()->with($this->toast($message, $result['failed'] > 0 ? 'warning' : 'success'));
    }
}
