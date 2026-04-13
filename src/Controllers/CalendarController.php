<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Professional;

final class CalendarController
{
    public function index(): string
    {
        $pros = Professional::allByBusiness(Auth::businessId(), true);
        return view('dashboard/calendar', [
            'title' => 'Calendario',
            'pageTitle' => 'Calendario',
            'professionals' => $pros,
        ]);
    }

    public function events(): void
    {
        $from = (string) Request::query('start', date('Y-m-d'));
        $to = (string) Request::query('end', date('Y-m-d', strtotime('+30 days')));
        // FullCalendar envía ISO con T...; extraer fecha
        $from = substr($from, 0, 10);
        $to = substr($to, 0, 10);

        $bookings = Booking::forBusinessAndDateRange(Auth::businessId(), $from, $to);

        $events = array_map(fn($b) => [
            'id' => $b['id'],
            'title' => $b['client_name'] . ' · ' . $b['service_name'],
            'start' => $b['date'] . 'T' . substr($b['start_time'], 0, 5),
            'end' => $b['date'] . 'T' . substr($b['end_time'], 0, 5),
            'backgroundColor' => $b['service_color'] ?? '#4f46e5',
            'borderColor' => $b['professional_color'] ?? '#4f46e5',
            'extendedProps' => [
                'professional' => $b['professional_name'],
                'service' => $b['service_name'],
                'client' => $b['client_name'],
                'phone' => $b['client_phone'],
                'status' => $b['status'],
                'notes' => $b['notes'],
            ],
        ], $bookings);

        json_response($events);
    }
}
