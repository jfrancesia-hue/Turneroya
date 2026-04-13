<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Models\Business;
use TurneroYa\Models\Booking;

final class DashboardController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        if (!$businessId) redirect('/dashboard/onboarding');

        $business = Business::find($businessId);
        $stats = Business::stats($businessId);

        // Próximos 5 turnos
        $upcoming = Booking::forBusinessAndDateRange(
            $businessId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+7 days'))
        );
        $upcoming = array_slice($upcoming, 0, 5);

        return view('dashboard/index', [
            'title' => 'Inicio',
            'pageTitle' => 'Inicio',
            'business' => $business,
            'stats' => $stats,
            'upcoming' => $upcoming,
        ]);
    }
}
