<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

use TurneroYa\Core\Request;
use TurneroYa\Services\RateLimiter;

/**
 * Middleware base para rate limiting. Las subclases declaran bucket/max/window.
 * Devuelve 429 JSON cuando se excede el límite.
 */
abstract class AbstractRateLimit
{
    abstract protected function bucket(): string;
    abstract protected function maxRequests(): int;
    abstract protected function windowSeconds(): int;

    public function handle(): void
    {
        $ip = Request::ip();
        $limiter = new RateLimiter($this->bucket(), $this->maxRequests(), $this->windowSeconds());
        if (!$limiter->check($ip)) {
            http_response_code(429);
            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: ' . (string) $this->windowSeconds());
            echo json_encode(['error' => 'rate_limited'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
