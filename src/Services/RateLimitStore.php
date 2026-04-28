<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Contrato para backends de rate limiting.
 * Permite inyectar un store en memoria para tests sin DB.
 */
interface RateLimitStore
{
    /**
     * Incrementa el contador para (bucket, ip, windowStart) en 1 y devuelve el nuevo valor.
     * Si la fila no existe, debe crearla con count=1.
     *
     * @param string             $bucket      Identificador del bucket (e.g. "webhook").
     * @param string             $ip          IP del cliente.
     * @param \DateTimeImmutable $windowStart Inicio truncado de la ventana.
     */
    public function incrementAndGet(string $bucket, string $ip, \DateTimeImmutable $windowStart): int;

    /**
     * Limpia entradas de ventanas anteriores a $olderThan (oportunístico).
     */
    public function purgeOlderThan(\DateTimeImmutable $olderThan): void;
}
