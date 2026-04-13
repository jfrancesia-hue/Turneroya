<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use TurneroYa\Core\Database;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Business;
use TurneroYa\Models\Service;
use TurneroYa\Models\Client;

/**
 * Capa transaccional para crear/cancelar/reprogramar bookings
 * con validación contra doble-booking (usa SlotCalculator + índice UNIQUE).
 */
final class BookingService
{
    public function __construct(private readonly string $businessId) {}

    /**
     * Crea un booking validando el slot contra el SlotCalculator.
     * @return array{id:string, number:int} id del booking y booking_number
     * @throws \RuntimeException si el slot ya no está disponible
     */
    public function createBooking(array $payload): array
    {
        $required = ['client_id', 'service_id', 'professional_id', 'date', 'start_time', 'source'];
        foreach ($required as $k) {
            if (empty($payload[$k]) && $k !== 'professional_id') {
                throw new \InvalidArgumentException("Falta el campo: $k");
            }
        }

        $service = Service::find($payload['service_id']);
        if (!$service || $service['business_id'] !== $this->businessId) {
            throw new \RuntimeException('Servicio inválido');
        }

        $duration = (int) $service['duration'];
        $calculator = new SlotCalculator($this->businessId);

        // Re-chequeo de disponibilidad (idempotente contra race conditions)
        if (!$calculator->isSlotAvailable(
            (string) $payload['date'],
            (string) $payload['start_time'],
            $duration,
            (string) $payload['professional_id']
        )) {
            throw new \RuntimeException('Ese horario ya no está disponible. Elegí otro.');
        }

        $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
        $start = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $payload['date'] . ' ' . $payload['start_time'],
            $tz
        );
        if (!$start) throw new \RuntimeException('Fecha/hora inválida');
        $end = $start->add(new DateInterval('PT' . $duration . 'M'));

        return Database::transaction(function () use ($payload, $start, $end, $service) {
            $bookingId = Booking::create([
                'business_id' => $this->businessId,
                'client_id' => $payload['client_id'],
                'service_id' => $payload['service_id'],
                'professional_id' => $payload['professional_id'] ?: null,
                'date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'status' => !empty($payload['auto_confirm']) ? 'CONFIRMED' : 'PENDING',
                'source' => $payload['source'] ?? 'WEB',
                'notes' => $payload['notes'] ?? null,
                'price' => $service['price'] ?? null,
            ]);

            Client::incrementBookings($payload['client_id']);

            Database::insert('booking_analytics', [
                'business_id' => $this->businessId,
                'type' => 'booking_created',
                'metadata' => json_encode([
                    'booking_id' => $bookingId,
                    'source' => $payload['source'],
                    'service_id' => $payload['service_id'],
                ]),
            ]);

            $row = Database::fetchOne('SELECT booking_number FROM bookings WHERE id = :id', ['id' => $bookingId]);
            return [
                'id' => $bookingId,
                'number' => (int) ($row['booking_number'] ?? 0),
            ];
        });
    }

    public function cancel(string $bookingId, string $reason = ''): void
    {
        Booking::updateStatus($bookingId, 'CANCELLED');
        Database::insert('booking_analytics', [
            'business_id' => $this->businessId,
            'type' => 'booking_cancelled',
            'metadata' => json_encode(['booking_id' => $bookingId, 'reason' => $reason]),
        ]);
    }

    public function reschedule(string $bookingId, string $newDate, string $newStartTime): void
    {
        $booking = Booking::find($bookingId);
        if (!$booking) throw new \RuntimeException('Booking no encontrado');
        if ($booking['business_id'] !== $this->businessId) throw new \RuntimeException('No autorizado');

        $service = Service::find($booking['service_id']);
        if (!$service) throw new \RuntimeException('Servicio no encontrado');

        $calculator = new SlotCalculator($this->businessId);
        if (!$calculator->isSlotAvailable($newDate, $newStartTime, (int) $service['duration'], $booking['professional_id'])) {
            throw new \RuntimeException('El nuevo horario no está disponible');
        }

        $tz = new DateTimeZone('America/Argentina/Buenos_Aires');
        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$newDate $newStartTime", $tz);
        if (!$start) throw new \RuntimeException('Fecha inválida');
        $end = $start->add(new DateInterval('PT' . ((int) $service['duration']) . 'M'));

        Booking::update($bookingId, [
            'date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'status' => 'CONFIRMED',
            'reminder_sent' => false,
        ]);
    }
}
