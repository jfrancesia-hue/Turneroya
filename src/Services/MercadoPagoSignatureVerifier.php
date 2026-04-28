<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Verificador de firmas de MercadoPago para webhooks (IPN/Webhooks v2).
 *
 * MercadoPago envía:
 *  - x-signature: "ts=1704908010,v1=abcdef0123..."
 *  - x-request-id: UUID del request
 *  - data.id en body o querystring
 *
 * Manifest a firmar: id:{dataId};request-id:{xRequestId};ts:{ts};
 * Algoritmo: HMAC-SHA256(manifest, secret) en hex, comparado con v1 vía hash_equals.
 *
 * Ref: https://www.mercadopago.com.ar/developers/es/docs/your-integrations/notifications/webhooks
 */
final class MercadoPagoSignatureVerifier
{
    public static function verify(string $xSignature, string $xRequestId, string $dataId, string $secret): bool
    {
        if ($xSignature === '' || $xRequestId === '' || $dataId === '' || $secret === '') {
            return false;
        }

        // Parsear "ts=...,v1=..."
        $parts = [];
        foreach (explode(',', $xSignature) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || !str_contains($segment, '=')) continue;
            [$k, $v] = explode('=', $segment, 2);
            $parts[trim($k)] = trim($v);
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;
        if ($ts === null || $v1 === null || $ts === '' || $v1 === '') {
            return false;
        }

        // Replay window: 5 min de tolerancia. ts puede venir en segundos o ms.
        $tsInt = (int) $ts;
        if ($tsInt > 1_000_000_000_000) $tsInt = intdiv($tsInt, 1000);
        $now = time();
        $tolerance = 300;
        if ($tsInt <= 0 || abs($now - $tsInt) > $tolerance) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $xRequestId, $ts);
        $computed = hash_hmac('sha256', $manifest, $secret);
        return hash_equals($computed, $v1);
    }
}
