<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Models\Booking;

final class AnalyticsController
{
    public function index(): string
    {
        return view('dashboard/analytics/index', [
            'title' => 'Analytics',
            'pageTitle' => 'Analytics',
        ]);
    }

    public function data(): void
    {
        $days = (int) Request::query('days', 30);
        if ($days <= 0 || $days > 365) $days = 30;
        json_response(Booking::analytics(Auth::businessId(), $days));
    }
}
