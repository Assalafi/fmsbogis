<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersAndFiscalYearsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bogis.gov.ng'],
            [
                'name' => 'BOGIS Administrator',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $admin->assignRole('Super Admin');

        User::firstOrCreate(
            ['email' => 'finance@bogis.gov.ng'],
            [
                'name' => 'Finance Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        )->assignRole('Finance Admin');

        FiscalYear::firstOrCreate(
            ['name' => (string) now()->year],
            [
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'open',
            ]
        );

        FiscalYear::firstOrCreate(
            ['name' => (string) now()->addYear()->year],
            [
                'start_date' => now()->addYear()->startOfYear()->toDateString(),
                'end_date' => now()->addYear()->endOfYear()->toDateString(),
                'status' => 'closed',
            ]
        );
    }
}
