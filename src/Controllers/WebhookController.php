<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Request;
use TurneroYa\Models\Business;
use TurneroYa\Services\BotEngine;

final class WebhookController
{
    /**
     * Webhook de Twilio para mensajes entrantes de WhatsApp.
     * Twilio envía: From (whatsapp:+549...), To (whatsapp:+549...), Body
     */
    public function whatsapp(): void
    {
        $from = (string) Request::input('From', '');
        $to = (string) Request::input('To', '');
        $body = trim((string) Request::input('Body', ''));

        if (!$from || !$body) {
            $this->twiml('');
            return;
        }

        // Normalizar "whatsapp:+549..." → "+549..."
        $fromNumber = preg_replace('/^whatsapp:/', '', $from);
        $toNumber = preg_replace('/^whatsapp:/', '', $to);

        // Resolver negocio por número de destino
        $business = $this->resolveBusinessByNumber((string) $toNumber);
        if (!$business) {
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

        $this->twiml($reply);
    }

    public function mercadopago(): void
    {
        $body = file_get_contents('php://input') ?: '';
        error_log('[MercadoPago webhook] ' . $body);

        // MP envía el topic por querystring o por body (según v1/v2 del webhook)
        $topic = (string) (Request::input('topic') ?? Request::input('type') ?? '');
        $resourceId = (string) Request::input('id', '');

        if (!$topic || !$resourceId) {
            $payload = json_decode($body, true);
            if (is_array($payload)) {
                $topic = (string) ($payload['type'] ?? $payload['topic'] ?? $topic);
                $resourceId = (string) ($payload['data']['id'] ?? $payload['id'] ?? $resourceId);
            }
        }

        if ($topic && $resourceId) {
            try {
                (new \TurneroYa\Services\SubscriptionService())->handleWebhook($topic, $resourceId);
            } catch (\Throwable $e) {
                error_log('[MP webhook subscription handler] ' . $e->getMessage());
            }
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
