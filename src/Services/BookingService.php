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
     * Helper puro: ¿este servicio + payload requiere cobro de seña?
     * Extraído para poder testear los edge cases sin DB.
     */
    public static function shouldRequireDeposit(array $service, array $payload): bool
    {
        if (!empty($payload['skip_deposit'])) return false;
        if (empty($service['requires_deposit'])) return false;
        if (empty($service['deposit_amount']) || (float) $service['deposit_amount'] <= 0) return false;
        return true;
    }

    /**
     * Crea un booking validando el slot contra el SlotCalculator.
     * Si el servicio requiere seña, el booking queda en PENDING_PAYMENT
     * con un link de pago de MP y vencimiento de 15 minutos por default.
     *
     * @return array{id:string, number:int} (más campos cuando requires_payment)
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

        if (self::shouldRequireDeposit($service, $payload)) {
            return $this->createBookingWithDeposit($payload, $service, $duration);
        }

        return $this->createBookingDirect($payload, $service, $duration);
    }

    /**
     * Crea el booking de forma directa (sin cobro previo de seña).
     */
    private function createBookingDirect(array $payload, array $service, int $duration): array
    {
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

    /**
     * Crea el booking en estado PENDING_PAYMENT y devuelve el link de MP.
     * Si la creación de la preference de MP falla, la transacción se aborta
     * y no quedan bookings huérfanos.
     */
    private function createBookingWithDeposit(array $payload, array $service, int $duration): array
    {
        $calculator = new SlotCalculator($this->businessId);
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

        $expiresMinutes = (int) (config('services.payment.expiration_minutes', 15));
        if ($expiresMinutes <= 0) $expiresMinutes = 15;
        $expiresAt = (new DateTimeImmutable('now', $tz))->add(new DateInterval('PT' . $expiresMinutes . 'M'));

        return Database::transaction(function () use ($payload, $start, $end, $service, $expiresAt) {
            $bookingId = Booking::create([
                'business_id' => $this->businessId,
                'client_id' => $payload['client_id'],
                'service_id' => $payload['service_id'],
                'professional_id' => $payload['professional_id'] ?: null,
                'date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'status' => 'PENDING_PAYMENT',
                'source' => $payload['source'] ?? 'WEB',
                'notes' => $payload['notes'] ?? null,
                'price' => $service['price'] ?? null,
                'payment_expires_at' => $expiresAt->format('c'),
            ]);

            // Crear preference MP. Si falla, se aborta la transacción y no
            // queda un booking PENDING_PAYMENT sin link de pago.
            $initPoint = (new MercadoPagoService())->createDepositPreference($bookingId);
            if ($initPoint === '') {
                throw new \RuntimeException('No se pudo generar el link de pago. Probá de nuevo.');
            }
            Booking::update($bookingId, ['payment_init_point' => $initPoint]);

            Database::insert('booking_analytics', [
                'business_id' => $this->businessId,
                'type' => 'booking_pending_payment',
                'metadata' => json_encode([
                    'booking_id' => $bookingId,
                    'source' => $payload['source'] ?? 'WEB',
                    'service_id' => $payload['service_id'],
                    'amount' => (float) $service['deposit_amount'],
                ]),
            ]);

            $row = Database::fetchOne('SELECT booking_number FROM bookings WHERE id = :id', ['id' => $bookingId]);
            return [
                'id' => $bookingId,
                'number' => (int) ($row['booking_number'] ?? 0),
                'requires_payment' => true,
                'payment_url' => $initPoint,
                'expires_at' => $expiresAt->format('c'),
                'deposit_amount' => (float) $service['deposit_amount'],
            ];
        });
    }

    /**
     * Cancela bookings con status PENDING_PAYMENT que pasaron su payment_expires_at.
     * Dispara waitlist sobre cada slot recién liberado (best-effort).
     *
     * @return int cantidad de bookings expirados
     */
    public function expirePendingPayments(): int
    {
        $expired = Database::fetchAll(
            "SELECT id FROM bookings
             WHERE business_id = :biz
               AND status = 'PENDING_PAYMENT'
               AND payment_expires_at < NOW()
             ORDER BY payment_expires_at ASC
             LIMIT 100",
            ['biz' => $this->businessId]
        );

        $count = 0;
        foreach ($expired as $row) {
            try {
                Booking::updateStatus($row['id'], 'CANCELLED');
                Database::insert('booking_analytics', [
                    'business_id' => $this->businessId,
                    'type' => 'booking_payment_expired',
                    'metadata' => json_encode(['booking_id' => $row['id']]),
                ]);
                $count++;
                // Disparar waitlist sobre el slot recién liberado (best-effort)
                try {
                    (new WaitlistService($this->businessId))->notifyOnSlotFreed($row['id']);
                } catch (\Throwable $e) {
                    error_log('[BookingService::expirePendingPayments] waitlist: ' . $e->getMessage());
                }
            } catch (\Throwable $e) {
                error_log('[BookingService::expirePendingPayments] ' . $e->getMessage());
            }
        }
        return $count;
    }

    public function cancel(string $bookingId, string $reason = ''): void
    {
        Booking::updateStatus($bookingId, 'CANCELLED');
        Database::insert('booking_analytics', [
            'business_id' => $this->businessId,
            'type' => 'booking_cancelled',
            'metadata' => json_encode(['booking_id' => $bookingId, 'reason' => $reason]),
        ]);

        // Notificar a la lista de espera (best-effort, no falla el cancel si tira)
        try {
            (new WaitlistService($this->businessId))->notifyOnSlotFreed($bookingId);
        } catch (\Throwable $e) {
            error_log('[BookingService] waitlist notify failed: ' . $e->getMessage());
        }
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

        // Datos del slot original — el reschedule libera ese hueco, así que
        // también puede disparar waitlist.
        $oldDate = (string) $booking['date'];
        $oldStart = substr((string) $booking['start_time'], 0, 5);

        Booking::update($bookingId, [
            'date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'status' => 'CONFIRMED',
            'reminder_sent' => false,
        ]);

        // Notificar waitlist sobre el slot viejo que quedó libre
        try {
            (new WaitlistService($this->businessId))->notifyOnSlotFreedRaw(
                (string) $booking['service_id'],
                $booking['professional_id'] ?? null,
                $oldDate,
                $oldStart
            );
        } catch (\Throwable $e) {
            error_log('[BookingService] waitlist notify failed (reschedule): ' . $e->getMessage());
        }
    }
}
