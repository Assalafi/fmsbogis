<?php

namespace App\Console\Commands;

use App\Models\FiscalYear;
use App\Services\EBudgetSyncService;
use App\Support\ActiveFiscalYear;
use Illuminate\Console\Command;
use Throwable;

class SyncEBudget extends Command
{
    protected $signature = 'ebudget:sync {session? : Four-digit fiscal year, for example 2026}';

    protected $description = 'Synchronise BOGIS approved budgets, virements, and clearances from eBudget';

    public function handle(EBudgetSyncService $syncService): int
    {
        $session = $this->argument('session');

        if ($session !== null && ! preg_match('/^20\d{2}$/', (string) $session)) {
            $this->error('The session must be a four-digit fiscal year, for example 2026.');

            return self::INVALID;
        }

        $fiscalYear = $session
            ? FiscalYear::firstOrCreate(
                ['name' => (string) $session],
                [
                    'start_date' => $session.'-01-01',
                    'end_date' => $session.'-12-31',
                    'status' => (string) $session === now()->format('Y') ? 'open' : 'closed',
                ]
            )
            : ActiveFiscalYear::get();

        if (! $fiscalYear) {
            $this->error('No open fiscal year exists. Pass the year explicitly.');

            return self::FAILURE;
        }

        $this->info("Synchronising BOGIS budget data for {$fiscalYear->name}...");

        try {
            $run = $syncService->sync($fiscalYear);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($run->message);

        return self::SUCCESS;
    }
}
