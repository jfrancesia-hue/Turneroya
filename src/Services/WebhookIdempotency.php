<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Core\Database;

/**
 * Idempotencia de webhooks vía tabla webhook_events.
 *
 * Convención: el caller llama claim() apenas recibe el evento con (provider, externalId).
 * Si claim() devuelve true, el evento es nuevo y debe procesarse.
 * Tras procesarlo, llamar markProcessed() para dejar trazabilidad.
 */
final class WebhookIdempotency
{
    /**
     * Reclama un evento. Devuelve true si es nuevo (procesar), false si ya existía.
     *
     * @param string                $provider    Uno de: 'twilio', 'mercadopago'.
     * @param string                $externalId  ID externo único (MessageSid, topic+resource, etc.).
     * @param array<string, mixed>|null $payload Payload opcional para auditoría.
     */
    public static function claim(string $provider, string $externalId, ?array $payload = null): bool
    {
        if ($externalId === '') return true; // sin ID no podemos garantizar idempotencia, dejar pasar

        $sql = 'INSERT INTO webhook_events (provider, external_id, payload)
                VALUES (:provider, :external_id, :payload)
                ON CONFLICT (provider, external_id) DO NOTHING
                RETURNING id';

        try {
            $row = Database::fetchOne($sql, [
                'provider' => $provider,
                'external_id' => $externalId,
                'payload' => $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable $e) {
            // Si la tabla no existe (entornos sin migrar), no rompemos el webhook.
            error_log('[WebhookIdempotency] ' . $e->getMessage());
            return true;
        }

        return $row !== null && isset($row['id']);
    }

    public static function markProcessed(string $provider, string $externalId): void
    {
        if ($externalId === '') return;
        try {
            Database::query(
                'UPDATE webhook_events SET processed_at = NOW()
                 WHERE provider = :provider AND external_id = :external_id',
                ['provider' => $provider, 'external_id' => $externalId]
            );
        } catch (\Throwable $e) {
            error_log('[WebhookIdempotency::markProcessed] ' . $e->getMessage());
        }
    }
}
