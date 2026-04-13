<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Core\Validator;
use TurneroYa\Models\Business;
use TurneroYa\Models\Service;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Client;
use TurneroYa\Services\SlotCalculator;
use TurneroYa\Services\BookingService;
use TurneroYa\Services\NotificationService;

final class PublicBookingController
{
    public function show(array $params): string
    {
        $business = Business::findBySlug($params['slug']);
        if (!$business) {
            http_response_code(404);
            return view('errors/404');
        }
        $services = Service::allByBusiness($business['id'], true);
        return view('public/booking', [
            'title' => 'Reservar en ' . $business['name'],
            'business' => $business,
            'services' => $services,
        ]);
    }

    public function slots(array $params): void
    {
        $business = Business::findBySlug($params['slug']);
        if (!$business) json_response(['error' => 'not_found'], 404);

        $serviceId = (string) Request::input('service_id');
        $professionalId = Request::input('professional_id') ?: null;
        $date = (string) Request::input('date');

        if (!$serviceId || !$date) json_response(['error' => 'missing_params'], 400);

        $calculator = new SlotCalculator($business['id']);
        $slots = $calculator->getAvailableSlots($date, $serviceId, $professionalId);

        // Si no se especificó profesional, devolver también profesionales disponibles
        $professionals = [];
        if (!$professionalId) {
            $professionals = Professional::professionalsForService($serviceId);
        }

        json_response([
            'slots' => $slots,
            'professionals' => $professionals,
        ]);
    }

    public function confirm(array $params): void
    {
        $business = Business::findBySlug($params['slug']);
        if (!$business) json_response(['error' => 'not_found'], 404);

        $data = [
            'service_id' => (string) Request::input('service_id'),
            'professional_id' => (string) Request::input('professional_id'),
            'date' => (string) Request::input('date'),
            'start_time' => (string) Request::input('start_time'),
            'client_name' => trim((string) Request::input('client_name', '')),
            'client_phone' => trim((string) Request::input('client_phone', '')),
            'client_email' => trim((string) Request::input('client_email', '')),
            'notes' => trim((string) Request::input('notes', '')),
        ];

        $v = Validator::make($data, [
            'service_id' => 'required',
            'date' => 'required|date',
            'start_time' => 'required|regex:/^\d{2}:\d{2}$/',
            'client_name' => 'required|min:2|max:100',
            'client_phone' => 'required|min:6|max:30',
            'client_email' => 'email',
        ]);
        if ($v->fails()) json_response(['error' => $v->firstError()], 422);

        try {
            // Buscar/crear cliente
            $existing = Client::findByPhoneOrWhatsapp($business['id'], $data['client_phone']);
            if ($existing) {
                $clientId = $existing['id'];
                Client::update($clientId, [
                    'name' => $data['client_name'],
                    'email' => $data['client_email'] ?: $existing['email'],
                    'whatsapp_number' => Client::normalizePhone($data['client_phone']),
                ]);
            } else {
                $clientId = Client::create([
                    'business_id' => $business['id'],
                    'name' => $data['client_name'],
                    'phone' => Client::normalizePhone($data['client_phone']),
                    'whatsapp_number' => Client::normalizePhone($data['client_phone']),
                    'email' => $data['client_email'] ?: null,
                ]);
            }

            $service = new BookingService($business['id']);
            $result = $service->createBooking([
                'client_id' => $clientId,
                'service_id' => $data['service_id'],
                'professional_id' => $data['professional_id'] ?: null,
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'source' => 'WEB',
                'notes' => $data['notes'] ?: null,
                'auto_confirm' => !$business['require_confirmation'],
            ]);

            // Enviar notificación de confirmación (best-effort, no bloqueante)
            try {
                (new NotificationService())->sendBookingConfirmation($result['id']);
            } catch (\Throwable $e) {
                error_log('Notification failed: ' . $e->getMessage());
            }

            json_response([
                'success' => true,
                'booking_id' => $result['id'],
                'booking_number' => $result['number'],
                'redirect' => '/book/' . $business['slug'] . '/success/' . $result['id'],
            ]);
        } catch (\Throwable $e) {
            json_response(['error' => $e->getMessage()], 422);
        }
    }

    public function success(array $params): string
    {
        $business = Business::findBySlug($params['slug']);
        $booking = Booking::findWithRelations($params['bookingId']);
        if (!$business || !$booking || $booking['business_id'] !== $business['id']) {
            http_response_code(404);
            return view('errors/404');
        }
        return view('public/success', [
            'title' => '¡Turno confirmado!',
            'business' => $business,
            'booking' => $booking,
        ]);
    }
}
