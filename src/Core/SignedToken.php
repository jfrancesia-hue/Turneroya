<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Tokens firmados con HMAC-SHA256 — útiles para endpoints públicos donde
 * no hay sesión pero queremos prevenir POSTs cross-site automatizados.
 *
 * Formato: "<ts>.<hmac>"  (ts = unix epoch, hmac sobre "audience:ts" con APP_KEY)
 *
 * No reemplaza CSRF de sesión: complementa para endpoints sin login.
 */
final class SignedToken
{
    /**
     * Emite un token vinculado a una "audience" (ej: el slug del negocio).
     */
    public static function issue(string $audience, ?int $now = null): string
    {
        $ts = $now ?? time();
        $hmac = self::sign($audience, $ts);
        return $ts . '.' . $hmac;
    }

    /**
     * Verifica que el token sea válido para la audience y no esté expirado.
     * Devuelve false si el formato es inválido, el HMAC no matchea, o el TTL pasó.
     */
    public static function verify(string $audience, string $token, int $maxAgeSeconds = 3600, ?int $now = null): bool
    {
        if ($token === '' || !str_contains($token, '.')) return false;
        [$tsRaw, $hmac] = explode('.', $token, 2);
        if (!ctype_digit($tsRaw) || $hmac === '') return false;

        $ts = (int) $tsRaw;
        $current = $now ?? time();
        if ($ts > $current + 60) return false;            // futuro: clock skew tolerado
        if ($current - $ts > $maxAgeSeconds) return false; // expirado

        $expected = self::sign($audience, $ts);
        return hash_equals($expected, $hmac);
    }

    private static function sign(string $audience, int $ts): string
    {
        $secret = self::secret();
        return hash_hmac('sha256', $audience . ':' . $ts, $secret);
    }

    private static function secret(): string
    {
        $key = (string) ($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?? '');
        if ($key !== '') return $key;

        // Fallback determinístico: derivamos un secreto estable de otros valores
        // del entorno cuando APP_KEY aún no fue configurado. No es ideal, pero
        // evita romper deploys que vienen sin APP_KEY del .env.example.
        $material = ($_ENV['CRON_SECRET'] ?? getenv('CRON_SECRET') ?: '') . '|'
                  . ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '') . '|'
                  . ($_ENV['APP_URL']     ?? getenv('APP_URL')     ?: '');
        if (trim($material, '|') === '') {
            throw new \RuntimeException('APP_KEY no está configurado y no hay secretos derivables del entorno.');
        }
        return hash('sha256', $material);
    }
}
