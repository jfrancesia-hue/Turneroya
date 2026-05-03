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

if (file_exists(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->load();
}
Config::load(BASE_PATH . '/config');

/**
 * Cada job tiene:
 *   - mode: 'delete' (default, borra filas viejas) o 'update' (preserva histórico)
 *   - table, col, days: ventana temporal
 *   - extra: cláusula AND opcional ("AND status = 'PENDING'")
 *   - set: para mode='update', sets a aplicar ("status = 'EXPIRED'")
 */
$jobs = [
    ['mode' => 'delete', 'table' => 'webhook_events',    'col' => 'received_at',     'days' => 90],
    ['mode' => 'delete', 'table' => 'rate_limits',       'col' => 'window_start',    'days' => 1],
    ['mode' => 'delete', 'table' => 'bot_conversations', 'col' => 'last_message_at', 'days' => 180],
    // Waitlist: las que pasaron expires_at y siguen PENDING se marcan EXPIRED
    // (no las borramos para preservar histórico de cuántos clientes esperaron).
    [
        'mode'  => 'update',
        'table' => 'waitlist_entries',
        'col'   => 'expires_at',
        'days'  => 0,
        'extra' => "AND status = 'PENDING'",
        'set'   => "status = 'EXPIRED', updated_at = NOW()",
    ],
];

$results = [];
foreach ($jobs as $job) {
    $mode = $job['mode'] ?? 'delete';
    $extra = isset($job['extra']) ? ' ' . $job['extra'] : '';
    try {
        if ($mode === 'update') {
            $sql = "UPDATE {$job['table']}
                       SET {$job['set']}
                     WHERE {$job['col']} < NOW() - INTERVAL '{$job['days']} days'{$extra}";
        } else {
            $sql = "DELETE FROM {$job['table']}
                     WHERE {$job['col']} < NOW() - INTERVAL '{$job['days']} days'{$extra}";
        }
        $count = Database::query($sql)->rowCount();
        $results[$job['table']] = ['mode' => $mode, 'rows' => $count];
    } catch (\Throwable $e) {
        $results[$job['table']] = ['error' => $e->getMessage()];
    }
}

echo json_encode([
    'ok'      => true,
    'at'      => date('c'),
    'results' => $results,
], JSON_PRETTY_PRINT) . "\n";
