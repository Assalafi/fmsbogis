<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class SyncProgress
{
    protected const KEY = 'bogis.forms-sync-progress';

    /**
     * @return array{
     *     status: string,
     *     since: string|null,
     *     until: string|null,
     *     page: int,
     *     created: int,
     *     existing: int,
     *     failed: int,
     *     total: int|null,
     *     started_at: string|null,
     *     finished_at: string|null,
     *     message: string|null
     * }
     */
    public static function state(): array
    {
        return Cache::get(self::KEY, [
            'status' => 'idle',
            'since' => null,
            'until' => null,
            'page' => 0,
            'created' => 0,
            'existing' => 0,
            'failed' => 0,
            'total' => null,
            'started_at' => null,
            'finished_at' => null,
            'message' => null,
        ]);
    }

    public static function start(?string $since, ?string $until): void
    {
        Cache::put(self::KEY, [
            'status' => 'running',
            'since' => $since,
            'until' => $until,
            'page' => 0,
            'created' => 0,
            'existing' => 0,
            'failed' => 0,
            'total' => null,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'message' => null,
        ], now()->addHours(2));
    }

    public static function update(int $page, int $created, int $existing, int $failed, ?int $total): void
    {
        $state = self::state();
        $state['status'] = 'running';
        $state['page'] = $page;
        $state['created'] = $created;
        $state['existing'] = $existing;
        $state['failed'] = $failed;
        $state['total'] = $total;
        $state['finished_at'] = null;
        $state['message'] = null;

        Cache::put(self::KEY, $state, now()->addHours(2));
    }

    public static function finish(int $created, int $existing, int $failed): void
    {
        $state = self::state();
        $state['status'] = 'done';
        $state['created'] = $created;
        $state['existing'] = $existing;
        $state['failed'] = $failed;
        $state['finished_at'] = now()->toIso8601String();
        $state['message'] = "Sync complete. Created: {$created}, already existing: {$existing}, failed: {$failed}.";

        Cache::put(self::KEY, $state, now()->addHours(2));
    }

    public static function fail(string $message): void
    {
        $state = self::state();
        $state['status'] = 'error';
        $state['finished_at'] = now()->toIso8601String();
        $state['message'] = $message;

        Cache::put(self::KEY, $state, now()->addHours(2));
    }

    public static function isRunning(): bool
    {
        $state = self::state();

        if ($state['status'] !== 'running') {
            return false;
        }

        $startedAt = $state['started_at'] ? now()->parse($state['started_at']) : null;

        if ($startedAt && $startedAt->diffInMinutes(now()) > 60) {
            self::fail('Sync appears to have stalled and was stopped. Please try again.');

            return false;
        }

        return true;
    }

    public static function percent(): int
    {
        $state = self::state();
        $done = $state['created'] + $state['existing'] + $state['failed'];
        $total = (int) ($state['total'] ?? 0);

        if ($total <= 0) {
            return $state['status'] === 'running' ? 5 : 0;
        }

        return (int) min(100, round($done / $total * 100));
    }
}
