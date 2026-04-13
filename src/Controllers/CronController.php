<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Request;
use TurneroYa\Models\Booking;
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
}
