<?php

namespace App\Console\Commands;

use App\Services\ExternalReceiptService;
use Illuminate\Console\Command;

class SyncReceiptsFromBogisForms extends Command
{
    protected $signature = 'receipts:sync-from-forms {--since= : Only sync payments paid on or after this date (Y-m-d)}';

    protected $description = 'Pull all paid payments from BOGIS Forms and create missing receipts.';

    public function handle(ExternalReceiptService $service): int
    {
        try {
            $result = $service->syncFromForms($this->option('since'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sync complete. Created: {$result['created']}, already existing: {$result['existing']}, failed: {$result['failed']}.");

        return self::SUCCESS;
    }
}
