<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Client;
use TurneroYa\Models\Service;
use TurneroYa\Models\Professional;
use TurneroYa\Services\BookingService;
use TurneroYa\Services\SlotCalculator;

final class BookingController
{
    public function index(): string
    {
        $businessId = Auth::businessId();
        $filter = (string) Request::query('filter', 'upcoming');
        if ($filter === 'past') {
            $bookings = Booking::forBusinessAndDateRange($businessId, '2000-01-01', date('Y-m-d', strtotime('-1 day')));
        } elseif ($filter === 'today') {
            $bookings = Booking::forBusinessAndDateRange($businessId, date('Y-m-d'), date('Y-m-d'));
        } else {
            $bookings = Booking::forBusinessAndDateRange($businessId, date('Y-m-d'), '2099-12-31');
        }
        return view('dashboard/bookings/index', [
            'title' => 'Turnos',
            'pageTitle' => 'Turnos',
            'bookings' => $bookings,
            'filter' => $filter,
        ]);
    }

    public function create(): string
    {
        $businessId = Auth::businessId();
        return view('dashboard/bookings/create', [
            'title' => 'Nuevo turno',
            'pageTitle' => 'Nuevo turno',
            'services' => Service::allByBusiness($businessId, true),
            'professionals' => Professional::allByBusiness($businessId, true),
            'clients' => Client::allByBusiness($businessId),
        ]);
    }

    public function store(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/bookings/create');
        }

        $data = [
            'service_id' => (string) Request::input('service_id'),
            'professional_id' => (string) Request::input('professional_id'),
            'date' => (string) Request::input('date'),
            'start_time' => (string) Request::input('start_time'),
            'client_id' => (string) Request::input('client_id'),
            'client_name_new' => trim((string) Request::input('client_name_new', '')),
            'client_phone_new' => trim((string) Request::input('client_phone_new', '')),
            'notes' => trim((string) Request::input('notes', '')),
        ];

        $v = Validator::make($data, [
            'service_id' => 'required',
            'date' => 'required|date',
            'start_time' => 'required',
            'professional_id' => 'required',
        ]);
        if ($v->fails()) {
            flash('error', $v->firstError() ?? 'Datos inválidos');
            redirect('/dashboard/bookings/create');
        }

        try {
            $businessId = Auth::businessId();
            // Cliente existente o nuevo
            if (!$data['client_id'] && $data['client_name_new'] && $data['client_phone_new']) {
                $data['client_id'] = Client::create([
                    'business_id' => $businessId,
                    'name' => $data['client_name_new'],
                    'phone' => Client::normalizePhone($data['client_phone_new']),
                    'whatsapp_number' => Client::normalizePhone($data['client_phone_new']),
                ]);
            }
            if (!$data['client_id']) {
                flash('error', 'Necesitás elegir o crear un cliente');
                redirect('/dashboard/bookings/create');
            }

            $service = new BookingService($businessId);
            $result = $service->createBooking([
                'client_id' => $data['client_id'],
                'service_id' => $data['service_id'],
                'professional_id' => $data['professional_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'source' => 'MANUAL',
                'notes' => $data['notes'] ?: null,
                'auto_confirm' => true,
            ]);
            flash('success', 'Turno #' . $result['number'] . ' creado');
            redirect('/dashboard/calendar');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/dashboard/bookings/create');
        }
    }

    public function show(array $params): string
    {
        $booking = Booking::findWithRelations($params['id']);
        if (!$booking || $booking['business_id'] !== Auth::businessId()) {
            flash('error', 'Turno no encontrado');
            redirect('/dashboard/bookings');
        }
        return view('dashboard/bookings/show', [
            'title' => 'Turno #' . $booking['booking_number'],
            'pageTitle' => 'Turno #' . $booking['booking_number'],
            'booking' => $booking,
        ]);
    }

    public function updateStatus(array $params): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) back();
        $status = (string) Request::input('status');
        $allowed = ['PENDING','CONFIRMED','COMPLETED','CANCELLED','NO_SHOW','RESCHEDULED'];
        if (!in_array($status, $allowed, true)) {
            flash('error', 'Estado inválido');
            back();
        }
        $b = Booking::find($params['id']);
        if (!$b || $b['business_id'] !== Auth::businessId()) back();
        Booking::updateStatus($params['id'], $status);
        if ($status === 'NO_SHOW') Client::incrementNoShow($b['client_id']);
        flash('success', 'Estado actualizado');
        redirect('/dashboard/bookings/' . $params['id']);
    }

    public function destroy(array $params): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) back();
        $b = Booking::find($params['id']);
        if ($b && $b['business_id'] === Auth::businessId()) {
            Booking::delete($params['id']);
            flash('success', 'Turno eliminado');
        }
        redirect('/dashboard/bookings');
    }

    public function availableSlots(): void
    {
        $businessId = Auth::businessId();
        $serviceId = (string) Request::input('service_id');
        $professionalId = Request::input('professional_id') ?: null;
        $date = (string) Request::input('date');
        if (!$serviceId || !$date) json_response(['slots' => []]);
        $calc = new SlotCalculator($businessId);
        json_response(['slots' => $calc->getAvailableSlots($date, $serviceId, $professionalId)]);
    }
}
