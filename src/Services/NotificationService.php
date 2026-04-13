<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use Twilio\Rest\Client as TwilioClient;
use TurneroYa\Models\Booking;
use TurneroYa\Models\Business;

/**
 * Envío de notificaciones (WhatsApp via Twilio + email via SMTP/Resend).
 */
final class NotificationService
{
    private ?TwilioClient $twilio = null;

    private function twilio(): ?TwilioClient
    {
        if ($this->twilio !== null) return $this->twilio;
        $sid = (string) config('services.twilio.account_sid');
        $token = (string) config('services.twilio.auth_token');
        if (!$sid || !$token) return null;
        $this->twilio = new TwilioClient($sid, $token);
        return $this->twilio;
    }

    public function sendWhatsApp(string $to, string $message): bool
    {
        $from = (string) config('services.twilio.whatsapp_from');
        if (!$from) return false;
        $client = $this->twilio();
        if (!$client) return false;

        $toFormatted = str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to;
        try {
            $client->messages->create($toFormatted, [
                'from' => $from,
                'body' => $message,
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('[NotificationService] WhatsApp error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendBookingConfirmation(string $bookingId): bool
    {
        $booking = Booking::findWithRelations($bookingId);
        if (!$booking) return false;
        $business = Business::find($booking['business_id']);
        if (!$business) return false;

        $to = $booking['client_whatsapp'] ?: $booking['client_phone'];
        if (!$to) return false;

        $date = (new \DateTimeImmutable($booking['date']))->format('d/m/Y');
        $start = substr($booking['start_time'], 0, 5);

        $msg = "¡Hola {$booking['client_name']}! 🎉\n\n"
             . "Tu turno en *{$business['name']}* está confirmado:\n\n"
             . "📅 {$date} a las {$start}\n"
             . "✂️ {$booking['service_name']}\n"
             . ($booking['professional_name'] ? "👤 {$booking['professional_name']}\n" : '')
             . "\nTurno #{$booking['booking_number']}\n"
             . "Si necesitás cancelar o cambiar, escribinos por acá.";

        Booking::update($bookingId, ['confirmation_sent' => true]);
        return $this->sendWhatsApp($to, $msg);
    }

    public function sendReminder(string $bookingId): bool
    {
        $booking = Booking::findWithRelations($bookingId);
        if (!$booking) return false;
        $business = Business::find($booking['business_id']);
        if (!$business) return false;

        $to = $booking['client_whatsapp'] ?: $booking['client_phone'];
        if (!$to) return false;

        $date = (new \DateTimeImmutable($booking['date']))->format('d/m');
        $start = substr($booking['start_time'], 0, 5);

        $msg = "👋 ¡Hola {$booking['client_name']}!\n\n"
             . "Te recordamos tu turno de mañana en *{$business['name']}*:\n\n"
             . "📅 {$date} a las {$start}\n"
             . "✂️ {$booking['service_name']}\n\n"
             . "¡Te esperamos! Si no podés venir, avisanos por acá.";

        Booking::markReminderSent($bookingId);
        return $this->sendWhatsApp($to, $msg);
    }
}
