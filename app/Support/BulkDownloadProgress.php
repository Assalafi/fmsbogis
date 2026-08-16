<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class BulkDownloadProgress
{
    public const MAX_SYNC = 100;

    public static function state(?string $token = null): array
    {
        if ($token === null) {
            $token = self::activeToken();
        }

        $default = [
            'token' => $token,
            'status' => 'idle',
            'total' => 0,
            'done' => 0,
            'failed' => 0,
            'zip_path' => null,
            'started_at' => null,
            'finished_at' => null,
            'message' => null,
        ];

        if ($token === null || $token === '') {
            return $default;
        }

        return Cache::get(self::key($token), $default);
    }

    public static function activeToken(): ?string
    {
        return Cache::get('bogis.bulk-download-active-token');
    }

    public static function start(int $total): string
    {
        $token = (string) \Illuminate\Support\Str::uuid();

        Cache::put(self::key($token), [
            'token' => $token,
            'status' => 'running',
            'total' => $total,
            'done' => 0,
            'failed' => 0,
            'zip_path' => null,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'message' => null,
        ], now()->addHours(2));

        Cache::put('bogis.bulk-download-active-token', $token, now()->addHours(2));

        return $token;
    }

    public static function update(string $token, int $done, int $failed): void
    {
        $state = self::state($token);
        $state['status'] = 'running';
        $state['done'] = $done;
        $state['failed'] = $failed;

        Cache::put(self::key($token), $state, now()->addHours(2));
    }

    public static function finish(string $token, string $zipPath, int $total, int $failed): void
    {
        $state = self::state($token);
        $state['status'] = 'done';
        $state['done'] = $total;
        $state['failed'] = $failed;
        $state['zip_path'] = $zipPath;
        $state['finished_at'] = now()->toIso8601String();
        $state['message'] = "Ready. {$total} receipt(s) packed.";

        Cache::put(self::key($token), $state, now()->addHours(2));
    }

    public static function fail(string $token, string $message): void
    {
        $state = self::state($token);
        $state['status'] = 'error';
        $state['finished_at'] = now()->toIso8601String();
        $state['message'] = $message;

        Cache::put(self::key($token), $state, now()->addHours(2));
    }

    public static function isRunning(?string $token = null): bool
    {
        $state = self::state($token);

        if ($state['status'] !== 'running') {
            return false;
        }

        $startedAt = $state['started_at'] ? now()->parse($state['started_at']) : null;

        if ($startedAt && $startedAt->diffInMinutes(now()) > 60) {
            self::fail($state['token'], 'Bulk download appears to have stalled and was stopped. Please try again.');

            return false;
        }

        return true;
    }

    public static function percent(string $token): int
    {
        $state = self::state($token);
        $total = (int) $state['total'];

        if ($total <= 0) {
            return 0;
        }

        return (int) min(100, round(((int) $state['done'] / $total) * 100));
    }

    protected static function key(string $token): string
    {
        return 'bogis.bulk-download.'.$token;
    }
}
