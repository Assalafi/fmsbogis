<?php

namespace App\Console\Commands;

use App\Services\ExternalReceiptService;
use App\Support\SyncProgress;
use Illuminate\Console\Command;

class SyncReceiptsFromBogisForms extends Command
{
    protected $signature = 'receipts:sync-from-forms
        {--since= : Only sync payments paid on or after this date (Y-m-d)}
        {--until= : Only sync payments paid on or before this date (Y-m-d)}';

    protected $description = 'Pull paid payments from BOGIS Forms (within a date range) and create missing receipts.';

    public function handle(ExternalReceiptService $service): int
    {
        $since = $this->option('since') ?: null;
        $until = $this->option('until') ?: null;

        $startedByRequest = SyncProgress::isRunning();

        if (! $startedByRequest) {
            SyncProgress::start($since, $until);
        }

        try {
            $result = $service->syncFromForms($since, $until, function ($page, $created, $existing, $failed, $total) {
                SyncProgress::update($page, $created, $existing, $failed, $total);

                $this->line("page {$page}: created {$created} / existing {$existing} / failed {$failed}");
            });

            SyncProgress::finish($result['created'], $result['existing'], $result['failed']);

            $this->info("Sync complete. Created: {$result['created']}, already existing: {$result['existing']}, failed: {$result['failed']}.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            SyncProgress::fail($e->getMessage());

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
