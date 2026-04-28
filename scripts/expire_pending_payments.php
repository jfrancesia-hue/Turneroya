<?php
/**
 * Script CLI: cancela bookings PENDING_PAYMENT vencidos.
 * Configurar en crontab cada minuto:
 *   * * * * * cd /path/to/turneroya && php scripts/expire_pending_payments.php >> storage/logs/expire_payments.log 2>&1
 *
 * O usar un servicio externo que haga GET a /api/payments/expire?secret=XXX
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use TurneroYa\Core\App;
use TurneroYa\Core\Database;
use TurneroYa\Services\BookingService;

$app = new App(BASE_PATH);
$app->boot();

$businesses = Database::fetchAll(
    "SELECT DISTINCT business_id FROM bookings
     WHERE status = 'PENDING_PAYMENT' AND payment_expires_at < NOW()"
);

$total = 0;
foreach ($businesses as $r) {
    $total += (new BookingService($r['business_id']))->expirePendingPayments();
}

echo json_encode(
    ['ok' => true, 'expired' => $total, 'businesses_processed' => count($businesses), 'at' => date('c')],
    JSON_PRETTY_PRINT
) . "\n";
