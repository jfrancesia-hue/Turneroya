<?php
/**
 * Script CLI para enviar recordatorios de turnos.
 * Configurar en crontab:
 *   0 * * * * cd /path/to/turneroya && php scripts/reminders_cron.php >> storage/logs/reminders.log 2>&1
 *
 * O usar un servicio externo que haga GET a /api/reminders/cron?secret=XXX
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TurneroYa\Core\Config;
use TurneroYa\Models\Booking;
use TurneroYa\Services\NotificationService;

Dotenv::createImmutable(BASE_PATH)->load();
Config::load(BASE_PATH . '/config');
date_default_timezone_set((string) Config::get('app.timezone', 'America/Argentina/Buenos_Aires'));

echo '[' . date('c') . "] Ejecutando recordatorios...\n";

try {
    $bookings = Booking::pendingReminders();
    echo '  → ' . count($bookings) . " turnos pendientes de recordar\n";

    $notif = new NotificationService();
    $sent = 0;
    foreach ($bookings as $b) {
        if ($notif->sendReminder($b['id'])) $sent++;
    }
    echo "  ✓ Enviados: $sent\n";
} catch (\Throwable $e) {
    echo '  ✗ ERROR: ' . $e->getMessage() . "\n";
    exit(1);
}
