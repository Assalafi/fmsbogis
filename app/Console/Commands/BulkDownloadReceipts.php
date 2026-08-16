<?php

namespace App\Console\Commands;

use App\Models\Receipt;
use App\Services\ReceiptPdfService;
use App\Support\BulkDownloadProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;

class BulkDownloadReceipts extends Command
{
    protected $signature = 'receipts:bulk-download
        {--token= : Progress token}
        {--ids= : Comma separated receipt UUIDs}
        {--filters= : JSON filter payload (fiscal_year_id, account_id, economic_code_id, status, search, date_from, date_to)}';

    protected $description = 'Generate a ZIP of receipt PDFs for selected or filtered receipts.';

    public function handle(ReceiptPdfService $pdfService): int
    {
        $token = (string) $this->option('token');
        $ids = array_filter(explode(',', (string) $this->option('ids')));
        $filters = json_decode((string) $this->option('filters'), true) ?: [];

        $this->cleanupStaleZips();

        $query = Receipt::query();

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $this->applyFilters($query, $filters);
        }

        $total = $query->count();

        if ($total === 0) {
            BulkDownloadProgress::fail($token, 'No receipts matched the selection.');

            return self::FAILURE;
        }

        $zipPath = 'bulk/receipts-'.$token.'.zip';
        $absoluteZip = Storage::disk('local')->path($zipPath);
        $zipName = 'BOGIS-Receipts-'.$token.'.zip';

        Storage::disk('local')->makeDirectory('bulk');

        // Stream the archive to disk incrementally (low memory usage).
        $stream = fopen($absoluteZip, 'wb');

        if ($stream === false) {
            BulkDownloadProgress::fail($token, 'Could not create the ZIP file on disk.');

            return self::FAILURE;
        }

        $zip = new ZipStream(outputName: $zipName, outputStream: $stream, sendHttpHeaders: false);

        $chunkSize = \App\Services\ReceiptPdfService::MERGE_CHUNK_SIZE;
        $chunkCount = (int) ceil($total / $chunkSize);

        $part = 0;
        $done = 0;
        $failed = 0;

        try {
            // Each chunk of receipts is merged into a single multi-page PDF.
            $query->chunkById($chunkSize, function ($receipts) use (&$part, &$done, &$failed, $zip, $pdfService, $token, $total, $chunkCount) {
                $part++;

                try {
                    $pdf = $pdfService->generateMerged($receipts);

                    $zip->addFile(
                        fileName: sprintf('BOGIS-Receipts-Part-%02d-of-%02d.pdf', $part, $chunkCount),
                        data: $pdf,
                    );
                } catch (\Throwable) {
                    $failed += count($receipts);
                }

                $done += count($receipts);
                BulkDownloadProgress::update($token, $done, $failed);

                $this->line("{$done}/{$total} packed (part {$part}/{$chunkCount})");
            });

            $zip->finish();
        } finally {
            fclose($stream);
        }

        BulkDownloadProgress::finish($token, $zipPath, $total, $failed);

        $this->info("Done. {$total} receipts packed into {$chunkCount} file(s), {$failed} failed.");

        return self::SUCCESS;
    }

    protected function cleanupStaleZips(): void
    {
        $files = Storage::disk('local')->files('bulk');

        foreach ($files as $file) {
            $modified = Storage::disk('local')->lastModified($file);

            if ($modified !== null && now()->subDays(1)->timestamp > $modified) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    protected function applyFilters($query, array $filters): void
    {
        if (! empty($filters['fiscal_year_id'])) {
            $query->where('fiscal_year_id', $filters['fiscal_year_id']);
        }

        if (! empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (! empty($filters['economic_code_id'])) {
            $query->where('economic_code_id', $filters['economic_code_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('from_whom_received_to_whom_paid', 'like', "%{$search}%")
                    ->orWhere('treasury_receipt_voucher_number', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%")
                    ->orWhere('payer_phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_of_transaction', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_of_transaction', '<=', $filters['date_to']);
        }
    }
}
