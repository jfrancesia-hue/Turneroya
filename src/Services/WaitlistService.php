<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Models\Booking;
use TurneroYa\Models\Business;
use TurneroYa\Models\Client;
use TurneroYa\Models\WaitlistEntry;

/**
 * Servicio de lista de espera. Permite agregar clientes y notificar
 * automáticamente cuando se libera un slot que matchea.
 */
final class WaitlistService
{
    public function __construct(private readonly string $businessId) {}

    /**
     * Agrega un cliente a la lista de espera. Devuelve el id de la entry creada.
     */
    public function addToWaitlist(array $data): string
    {
        $required = ['client_id', 'service_id', 'preferred_date_from'];
        foreach ($required as $k) {
            if (empty($data[$k])) {
                throw new \InvalidArgumentException("Falta el campo: $k");
            }
        }

        $from = (string) $data['preferred_date_from'];
        $to = isset($data['preferred_date_to']) && $data['preferred_date_to'] !== ''
            ? (string) $data['preferred_date_to']
            : (new \DateTimeImmutable($from))->modify('+14 days')->format('Y-m-d');

        $payload = [
            'business_id' => $this->businessId,
            'client_id' => (string) $data['client_id'],
            'service_id' => (string) $data['service_id'],
            'professional_id' => isset($data['professional_id']) && $data['professional_id'] !== ''
                ? (string) $data['professional_id']
                : null,
            'preferred_date_from' => $from,
            'preferred_date_to' => $to,
            'preferred_time_from' => isset($data['preferred_time_from']) && $data['preferred_time_from'] !== ''
                ? (string) $data['preferred_time_from']
                : null,
            'preferred_time_to' => isset($data['preferred_time_to']) && $data['preferred_time_to'] !== ''
                ? (string) $data['preferred_time_to']
                : null,
            'notes' => $data['notes'] ?? null,
            'source' => $data['source'] ?? 'WEB',
        ];

        return WaitlistEntry::create($payload);
    }

    /**
     * Llamado cuando se cancela un booking: busca una entry de waitlist que
     * matchee con el slot recién liberado y notifica al cliente.
     *
     * @return string|null id de la entry notificada, o null si no hubo match
     */
    public function notifyOnSlotFreed(string $cancelledBookingId): ?string
    {
        $booking = Booking::findWithRelations($cancelledBookingId);
        if (!$booking) return null;
        if ($booking['business_id'] !== $this->businessId) return null;

        return $this->notifyOnSlotFreedRaw(
            (string) $booking['service_id'],
            $booking['professional_id'] ?? null,
            (string) $booking['date'],
            substr((string) $booking['start_time'], 0, 5),
            $cancelledBookingId
        );
    }

    /**
     * Versión "raw": notifica para un slot definido por sus campos sueltos
     * (útil cuando el booking original ya fue mutado, ej. en reschedule).
     *
     * @return string|null id de la entry notificada, o null si no hubo match
     */
    public function notifyOnSlotFreedRaw(
        string $serviceId,
        ?string $professionalId,
        string $date,
        string $startTime,
        ?string $hintBookingId = null
    ): ?string {
        $entry = WaitlistEntry::findMatchingEntry(
            $this->businessId,
            $serviceId,
            $professionalId,
            $date,
            $startTime
        );
        if (!$entry) return null;

        // CAS atómico: solo notificar si la entry sigue en PENDING.
        // Si otro proceso ganó la carrera (dos cancels concurrentes), devuelve
        // false → skipear el envío para no notificar dos veces al mismo cliente.
        $claimed = WaitlistEntry::tryClaim($entry['id'], $hintBookingId);
        if (!$claimed) return null;

        $this->sendNotification($entry, $serviceId, $date, $startTime);

        return $entry['id'];
    }

    private function sendNotification(array $entry, string $serviceId, string $date, string $startTime): void
    {
        $client = Client::find($entry['client_id']);
        if (!$client) return;
        $business = Business::find($this->businessId);
        if (!$business) return;

        $to = $client['whatsapp_number'] ?: $client['phone'];
        if (!$to) return;

        // Datos del servicio para mostrar el nombre en el mensaje
        $service = \TurneroYa\Models\Service::find($serviceId);
        $serviceName = $service['name'] ?? 'tu servicio';

        $dateFmt = (new \DateTimeImmutable($date))->format('d/m');
        $bookingUrl = url('/book/' . $business['slug']);

        $msg = "👋 ¡Hola {$client['name']}!\n\n"
             . "Se liberó un horario que estabas esperando en *{$business['name']}*:\n\n"
             . "📅 {$dateFmt} a las {$startTime}\n"
             . "✂️ {$serviceName}\n\n"
             . "Es por orden de llegada — apurate. Reservá acá:\n"
             . $bookingUrl;

        (new NotificationService())->sendWhatsApp($to, $msg);
    }
}
