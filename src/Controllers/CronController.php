<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Database;
use TurneroYa\Core\Request;
use TurneroYa\Models\Booking;
use TurneroYa\Services\BookingService;
use TurneroYa\Services\NotificationService;

final class CronController
{
    public function reminders(): void
    {
        $secret = Request::header('X-Cron-Secret') ?: Request::query('secret');
        $expected = (string) config('services.cron.secret');
        if (!$expected || !hash_equals($expected, (string) $secret)) {
            http_response_code(403);
            json_response(['error' => 'forbidden']);
        }

        $bookings = Booking::pendingReminders();
        $notif = new NotificationService();
        $sent = 0;
        $failed = 0;

        foreach ($bookings as $b) {
            $ok = $notif->sendReminder($b['id']);
            $ok ? $sent++ : $failed++;
        }

        json_response([
            'ok' => true,
            'processed' => count($bookings),
            'sent' => $sent,
            'failed' => $failed,
            'at' => date('c'),
        ]);
    }

    /**
     * Cancela bookings PENDING_PAYMENT vencidos y libera el slot.
     * Itera todos los negocios con bookings pendientes. Protegido por X-Cron-Secret.
     */
    public function expirePayments(): void
    {
        $secret = Request::header('X-Cron-Secret') ?: Request::query('secret');
        $expected = (string) config('services.cron.secret');
        if (!$expected || !hash_equals($expected, (string) $secret)) {
            http_response_code(403);
            json_response(['error' => 'forbidden']);
        }

        $businesses = Database::fetchAll(
            "SELECT DISTINCT business_id FROM bookings
             WHERE status = 'PENDING_PAYMENT' AND payment_expires_at < NOW()"
        );

        $totalExpired = 0;
        foreach ($businesses as $row) {
            $count = (new BookingService($row['business_id']))->expirePendingPayments();
            $totalExpired += $count;
        }

        json_response([
            'ok' => true,
            'expired' => $totalExpired,
            'businesses_processed' => count($businesses),
            'at' => date('c'),
        ]);
    }
}
