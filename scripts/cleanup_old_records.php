#!/usr/bin/env php
<?php
/**
 * Limpieza periódica de registros obsoletos.
 * Configurar en crontab:
 *   0 3 * * * cd /path/to/turneroya && php scripts/cleanup_old_records.php >> storage/logs/cleanup.log 2>&1
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TurneroYa\Core\Config;
use TurneroYa\Core\Database;

Dotenv::createImmutable(BASE_PATH)->load();
Config::load(BASE_PATH . '/config');

$jobs = [
    ['table' => 'webhook_events',   'col' => 'received_at',     'days' => 90],
    ['table' => 'rate_limits',      'col' => 'window_start',    'days' => 1],
    ['table' => 'bot_conversations','col' => 'last_message_at', 'days' => 180],
];

$results = [];
foreach ($jobs as $job) {
    try {
        $count = Database::query(
            "DELETE FROM {$job['table']} WHERE {$job['col']} < NOW() - INTERVAL '{$job['days']} days'"
        )->rowCount();
        $results[$job['table']] = $count;
    } catch (\Throwable $e) {
        $results[$job['table']] = ['error' => $e->getMessage()];
    }
}

echo json_encode([
    'ok'      => true,
    'at'      => date('c'),
    'deleted' => $results,
], JSON_PRETTY_PRINT) . "\n";
