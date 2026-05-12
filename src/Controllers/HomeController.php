<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Database;

final class HomeController
{
    public function index(): string
    {
        return view('home');
    }

    public function health(): void
    {
        $checks = [
            'database' => $this->databaseHealthy(),
            'storage' => $this->storageHealthy(),
        ];
        $ok = !in_array(false, $checks, true);

        json_response([
            'ok' => $ok,
            'service' => 'reservia',
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    private function databaseHealthy(): bool
    {
        try {
            Database::connection()->query('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            error_log('[health] database failed: ' . $e->getMessage());
            return false;
        }
    }

    private function storageHealthy(): bool
    {
        $paths = [
            BASE_PATH . '/storage/logs',
            BASE_PATH . '/storage/sessions',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path) || !is_writable($path)) {
                error_log('[health] storage not writable: ' . $path);
                return false;
            }
        }

        return true;
    }
}
