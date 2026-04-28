<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Rate limiter por bucket+ip con ventana fija.
 *
 * Usa un store inyectable (PostgresRateLimitStore en prod, InMemoryRateLimitStore en tests).
 * La ventana se trunca al múltiplo de $windowSeconds desde epoch para que
 * todos los requests del mismo intervalo caigan en la misma fila.
 *
 * Limpieza oportunista: cada N llamadas (1 de 50) borra ventanas anteriores
 * a 1 hora atrás para evitar crecimiento ilimitado de la tabla.
 *
 * Limitación conocida: en el borde de una ventana, el burst real puede
 * llegar a 2x el límite configurado (60 req al final de ventana N + 60 al
 * principio de ventana N+1). Para protección DoS de webhooks es aceptable;
 * si necesitás sliding window, refactorizar el store.
 */
final class RateLimiter
{
    private RateLimitStore $store;
    private static int $callCount = 0;

    public function __construct(
        private readonly string $bucket,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
        ?RateLimitStore $store = null
    ) {
        $this->store = $store ?? new PostgresRateLimitStore();
    }

    /**
     * Devuelve true si el request está dentro del límite, false si lo excede.
     */
    public function check(string $ip): bool
    {
        $windowStart = $this->currentWindowStart();

        try {
            $count = $this->store->incrementAndGet($this->bucket, $ip, $windowStart);
        } catch (\Throwable $e) {
            // Falla del store no debería bloquear el servicio: log y permitir.
            error_log('[RateLimiter] store error: ' . $e->getMessage());
            return true;
        }

        // Limpieza oportunista (1 de cada 50 llamadas).
        self::$callCount++;
        if (self::$callCount % 50 === 0) {
            try {
                $this->store->purgeOlderThan(
                    (new \DateTimeImmutable('@' . (time() - 3600)))
                );
            } catch (\Throwable $e) {
                // No-op
            }
        }

        return $count <= $this->maxRequests;
    }

    private function currentWindowStart(): \DateTimeImmutable
    {
        $now = time();
        $aligned = $now - ($now % $this->windowSeconds);
        return new \DateTimeImmutable('@' . $aligned);
    }
}
