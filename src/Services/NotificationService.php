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

    /**
     * Envía un mensaje WhatsApp con Content Template (HX SID).
     * Permite quick replies / botones interactivos.
     *
     * @param array<string,string> $variables Variables del template (1 → "valor1", 2 → "valor2", ...)
     */
    public function sendWhatsAppTemplate(string $to, string $contentSid, array $variables = []): bool
    {
        $from = (string) config('services.twilio.whatsapp_from');
        if (!$from || !$contentSid) return false;
        $client = $this->twilio();
        if (!$client) return false;

        $toFormatted = str_starts_with($to, 'whatsapp:') ? $to : 'whatsapp:' . $to;
        try {
            $client->messages->create($toFormatted, [
                'from' => $from,
                'contentSid' => $contentSid,
                'contentVariables' => json_encode($variables, JSON_UNESCAPED_UNICODE) ?: '{}',
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('[NotificationService] Template error: ' . $e->getMessage());
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

        Booking::update($bookingId, ['confirmation_sent' => true]);

        // Si hay template configurado → usar botones interactivos
        $sid = (string) config('services.twilio.confirmation_content_sid');
        if ($sid !== '') {
            return $this->sendWhatsAppTemplate($to, $sid, [
                '1' => (string) $booking['client_name'],
                '2' => (string) $business['name'],
                '3' => $date,
                '4' => $start,
                '5' => (string) $booking['service_name'],
                '6' => (string) ($booking['booking_number'] ?? ''),
                // Payloads de botones (deben matchear los configurados en Twilio):
                '7' => WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_CANCEL, $bookingId),
                '8' => WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_RESCHEDULE, $bookingId),
            ]);
        }

        // Fallback texto plano (comportamiento actual)
        $msg = "¡Hola {$booking['client_name']}! 🎉\n\n"
             . "Tu turno en *{$business['name']}* está confirmado:\n\n"
             . "📅 {$date} a las {$start}\n"
             . "✂️ {$booking['service_name']}\n"
             . ($booking['professional_name'] ? "👤 {$booking['professional_name']}\n" : '')
             . "\nTurno #{$booking['booking_number']}\n"
             . "Si necesitás cancelar o cambiar, escribinos por acá.";

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

        Booking::markReminderSent($bookingId);

        // Si hay template configurado → usar botones interactivos
        $sid = (string) config('services.twilio.reminder_content_sid');
        if ($sid !== '') {
            return $this->sendWhatsAppTemplate($to, $sid, [
                '1' => (string) $booking['client_name'],
                '2' => (string) $business['name'],
                '3' => $date,
                '4' => $start,
                '5' => (string) $booking['service_name'],
                // Payloads de botones del template (deben matchear los configurados en Twilio):
                '6' => WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_CONFIRM, $bookingId),
                '7' => WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_CANCEL, $bookingId),
                '8' => WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_RESCHEDULE, $bookingId),
            ]);
        }

        // Fallback texto plano (comportamiento actual)
        $msg = "👋 ¡Hola {$booking['client_name']}!\n\n"
             . "Te recordamos tu turno de mañana en *{$business['name']}*:\n\n"
             . "📅 {$date} a las {$start}\n"
             . "✂️ {$booking['service_name']}\n\n"
             . "¡Te esperamos! Si no podés venir, avisanos por acá.";
        return $this->sendWhatsApp($to, $msg);
    }
}
