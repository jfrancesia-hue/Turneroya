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

    public function mercadopago(): void
    {
        $body = file_get_contents('php://input') ?: '';
        error_log('[MercadoPago webhook] ' . substr($body, 0, 500));

        // 1) Verificar firma PRIMERO (si hay secret configurado), antes de parsear nada más
        $secret = (string) (config('services.mercadopago.webhook_secret') ?? '');
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

        // 3) Validar payload — si falta topic o resourceId, devolver 400
        if (!$topic || !$resourceId) {
            error_log('[MP webhook] payload incompleto: ' . substr($body, 0, 500));
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
