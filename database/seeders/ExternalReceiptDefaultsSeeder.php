<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\EconomicCode;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class ExternalReceiptDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Models\User::first();

        $appFeeCode = EconomicCode::firstOrCreate(
            ['code' => '12010150'],
            [
                'name' => 'BOGIS Forms Application Fees',
                'type' => 'revenue',
                'account_type' => null,
                'description' => 'Application fees received through BOGIS Forms (Zainpay)',
                'status' => 'active',
                'created_by' => $user?->id,
            ]
        );

        $premiumCode = EconomicCode::firstOrCreate(
            ['code' => '12010151'],
            [
                'name' => 'Plot Premium and Allocation Fees',
                'type' => 'revenue',
                'account_type' => null,
                'description' => 'Plot premium / allocation fees received through BOGIS Forms (Zainpay)',
                'status' => 'active',
                'created_by' => $user?->id,
            ]
        );

        Setting::set('external_application_fee_code_id', $appFeeCode->id);
        Setting::set('external_premium_code_id', $premiumCode->id);

        $account = Account::active()->orderByRaw("CASE account_type WHEN 'overhead' THEN 0 ELSE 1 END")->orderBy('account_name')->first();

        if ($account) {
            Setting::set('external_receipt_account_id', $account->id);
        }
    }
}
