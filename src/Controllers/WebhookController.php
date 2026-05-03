<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Request;
use TurneroYa\Models\Business;
use TurneroYa\Services\BotEngine;
use TurneroYa\Services\TwilioSignatureVerifier;
use TurneroYa\Services\MercadoPagoSignatureVerifier;
use TurneroYa\Services\WebhookIdempotency;

final class WebhookController
{
    /**
     * Webhook de Twilio para mensajes entrantes de WhatsApp.
     * Twilio envía: From (whatsapp:+549...), To (whatsapp:+549...), Body, MessageSid
     */
    public function whatsapp(): void
    {
        // 1) Validación de firma (configurable)
        if (config('services.twilio.validate_signature') === true) {
            $signature = (string) (Request::header('X-Twilio-Signature') ?? '');
            $authToken = (string) config('services.twilio.auth_token', '');
            $url = TwilioSignatureVerifier::currentUrl();
            // Twilio firma con los params POST form-encoded.
            $params = $_POST;
            if (!TwilioSignatureVerifier::verify($url, $params, $signature, $authToken)) {
                error_log('[Webhook WhatsApp] firma inválida desde IP ' . Request::ip());
                http_response_code(403);
                echo 'invalid signature';
                exit;
            }
        }

        $from = (string) Request::input('From', '');
        $to = (string) Request::input('To', '');
        $body = trim((string) Request::input('Body', ''));
        $messageSid = (string) Request::input('MessageSid', '');

        if (!$from || !$body) {
            $this->twiml('');
            return;
        }

        // 2) Idempotencia: si ya procesamos este MessageSid, responder vacío
        if ($messageSid !== '') {
            $isNew = WebhookIdempotency::claim('twilio', $messageSid, $_POST);
            if (!$isNew) {
                $this->twiml('');
                return;
            }
        }

        // Normalizar "whatsapp:+549..." → "+549..."
        $fromNumber = preg_replace('/^whatsapp:/', '', $from);
        $toNumber = preg_replace('/^whatsapp:/', '', $to);

        // Resolver negocio por número de destino
        $business = $this->resolveBusinessByNumber((string) $toNumber);
        if (!$business) {
            if ($messageSid !== '') WebhookIdempotency::markProcessed('twilio', $messageSid);
            $this->twiml('No hay un negocio asociado a este número.');
            return;
        }

        // Si vino un ButtonPayload (quick reply), procesarlo determinísticamente
        // ANTES de invocar al bot — así apretar un botón es instantáneo y barato.
        $buttonPayload = (string) Request::input('ButtonPayload', '');
        if ($buttonPayload !== '') {
            $reply = $this->handleButtonPayload($business['id'], (string) $fromNumber, $buttonPayload);
            if ($reply !== null) {
                if ($messageSid !== '') WebhookIdempotency::markProcessed('twilio', $messageSid);
                $this->twiml($reply);
                return;
            }
            // Si el payload no era nuestro → caer al bot normal
        }

        try {
            $bot = new BotEngine($business['id']);
            $reply = $bot->handleMessage((string) $fromNumber, $body);
        } catch (\Throwable $e) {
            error_log('[Webhook WhatsApp] ' . $e->getMessage());
            $reply = 'Perdón, estoy teniendo problemas técnicos. Probá en unos minutos.';
        }

        if ($messageSid !== '') WebhookIdempotency::markProcessed('twilio', $messageSid);
        $this->twiml($reply);
    }

    /**
     * Procesa un ButtonPayload de WhatsApp (quick reply de un Content Template).
     * Devuelve el texto a responder, o null si el payload no es nuestro.
     */
    private function handleButtonPayload(string $businessId, string $whatsappNumber, string $payload): ?string
    {
        $parsed = \TurneroYa\Services\WhatsAppButtonPayloads::parse($payload);
        if ($parsed === null) return null;

        $bookingId = $parsed['booking_id'];
        $booking = \TurneroYa\Models\Booking::find($bookingId);
        if (!$booking || $booking['business_id'] !== $businessId) {
            return 'No encontré ese turno. ¿Querés que te ayude de otra forma?';
        }

        // Verificar que el cliente del booking matchee con el número que escribe
        $client = \TurneroYa\Models\Client::findByPhoneOrWhatsapp($businessId, $whatsappNumber);
        if (!$client || $client['id'] !== $booking['client_id']) {
            return 'Este turno no parece ser tuyo. Si pensás que es un error, avisanos.';
        }

        $service = new \TurneroYa\Services\BookingService($businessId);

        return match ($parsed['action']) {
            \TurneroYa\Services\WhatsAppButtonPayloads::ACTION_CONFIRM => $this->handleConfirm($bookingId),
            \TurneroYa\Services\WhatsAppButtonPayloads::ACTION_CANCEL => $this->handleCancelViaButton($service, $bookingId),
            \TurneroYa\Services\WhatsAppButtonPayloads::ACTION_RESCHEDULE => $this->handleRescheduleHint($booking),
            default => null,
        };
    }

    private function handleConfirm(string $bookingId): string
    {
        \TurneroYa\Models\Booking::updateStatus($bookingId, 'CONFIRMED');
        return '¡Listo! Tu turno quedó confirmado. Nos vemos. ✅';
    }

    private function handleCancelViaButton(\TurneroYa\Services\BookingService $service, string $bookingId): string
    {
        $service->cancel($bookingId, 'Cancelado por cliente desde botón WhatsApp');
        return 'Cancelé tu turno. Cuando quieras sacar uno nuevo, escribinos. 👋';
    }

    private function handleRescheduleHint(array $booking): string
    {
        return 'Para reagendar, decime qué día y hora te queda mejor. Por ejemplo: "el martes a las 15hs" y te ofrezco horarios disponibles.';
    }

    public function mercadopago(): void
    {
        $body = file_get_contents('php://input') ?: '';

        // 1) Verificar firma PRIMERO (si hay secret configurado), antes de parsear nada más
        $secret = (string) (config('services.mercadopago.webhook_secret') ?? '');
        if ($secret === '' && config('app.env') === 'production') {
            error_log('[MP webhook] MERCADOPAGO_WEBHOOK_SECRET no configurado');
            json_response(['error' => 'webhook_secret_not_configured'], 500);
            return;
        }

        if ($secret !== '') {
            $xSignature = (string) (Request::header('X-Signature') ?? '');
            $xRequestId = (string) (Request::header('X-Request-Id') ?? '');
            // dataId puede venir en query o body — pre-parsear sólo eso
            $dataId = (string) (Request::input('id') ?? '');
            if ($dataId === '' && $body !== '') {
                $tmp = json_decode($body, true);
                if (is_array($tmp)) {
                    $dataId = (string) ($tmp['data']['id'] ?? $tmp['id'] ?? '');
                }
            }
            if (!MercadoPagoSignatureVerifier::verify($xSignature, $xRequestId, $dataId, $secret)) {
                error_log('[MP webhook] firma inválida desde IP ' . Request::ip());
                http_response_code(403);
                echo 'invalid signature';
                exit;
            }
        }

        // 2) Parsear topic + resourceId (query o body)
        $topic = (string) (Request::input('topic') ?? Request::input('type') ?? '');
        $resourceId = (string) Request::input('id', '');
        $payloadArr = json_decode($body, true);
        if (!is_array($payloadArr)) $payloadArr = null;
        if ((!$topic || !$resourceId) && $payloadArr) {
            $topic = $topic ?: (string) ($payloadArr['type'] ?? $payloadArr['topic'] ?? '');
            $resourceId = $resourceId ?: (string) ($payloadArr['data']['id'] ?? $payloadArr['id'] ?? '');
        }
        error_log('[MercadoPago webhook] topic=' . ($topic ?: '-') . ' resource_id=' . ($resourceId ?: '-') . ' bytes=' . strlen($body));

        // 3) Validar payload — si falta topic o resourceId, devolver 400
        if (!$topic || !$resourceId) {
            error_log('[MP webhook] payload incompleto bytes=' . strlen($body));
            json_response(['error' => 'incomplete_payload'], 400);
            return;
        }

        // 4) Idempotencia con (topic + ':' + resourceId)
        $externalId = $topic . ':' . $resourceId;
        $isNew = WebhookIdempotency::claim('mercadopago', $externalId, $payloadArr);
        if (!$isNew) {
            json_response(['received' => true, 'duplicate' => true]);
            return;
        }

        // 5) Handler — si falla, NO marcar processed, devolver 500 para que MP reintente
        try {
            (new \TurneroYa\Services\SubscriptionService())->handleWebhook($topic, $resourceId);
            WebhookIdempotency::markProcessed('mercadopago', $externalId);
        } catch (\Throwable $e) {
            error_log('[MP webhook handler] ' . $e->getMessage());
            json_response(['error' => 'handler_failed'], 500);
            return;
        }

        json_response(['received' => true]);
    }

    private function resolveBusinessByNumber(string $number): ?array
    {
        if (!$number) return null;
        // Buscar negocio por campo whatsapp (coincidencia exacta o parcial)
        $normalized = preg_replace('/\D/', '', $number);
        $row = \TurneroYa\Core\Database::fetchOne(
            "SELECT * FROM businesses WHERE regexp_replace(coalesce(whatsapp, ''), '\\D', '', 'g') = :n LIMIT 1",
            ['n' => $normalized]
        );
        if ($row) return $row;
        // Fallback: si hay un único negocio en la base, devolverlo (modo single-tenant)
        return \TurneroYa\Core\Database::fetchOne('SELECT * FROM businesses ORDER BY created_at ASC LIMIT 1');
    }

    private function twiml(string $text): void
    {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response>';
        if ($text !== '') {
            echo '<Message>' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</Message>';
        }
        echo '</Response>';
        exit;
    }
}
