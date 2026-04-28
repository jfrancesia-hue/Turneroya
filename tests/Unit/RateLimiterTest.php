<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\InMemoryRateLimitStore;
use TurneroYa\Services\RateLimiter;

/**
 * Tests del RateLimiter usando el store en memoria — no requiere DB.
 */
final class RateLimiterTest extends TestCase
{
    public function test_allows_up_to_max_requests(): void
    {
        $store = new InMemoryRateLimitStore();
        $limiter = new RateLimiter('test', maxRequests: 3, windowSeconds: 60, store: $store);
        $ip = '1.2.3.4';

        $this->assertTrue($limiter->check($ip));
        $this->assertTrue($limiter->check($ip));
        $this->assertTrue($limiter->check($ip));
    }

    public function test_blocks_after_max_requests(): void
    {
        $store = new InMemoryRateLimitStore();
        $limiter = new RateLimiter('test', maxRequests: 2, windowSeconds: 60, store: $store);
        $ip = '1.2.3.4';

        $this->assertTrue($limiter->check($ip));
        $this->assertTrue($limiter->check($ip));
        $this->assertFalse($limiter->check($ip));
        $this->assertFalse($limiter->check($ip));
    }

    public function test_separates_buckets(): void
    {
        $store = new InMemoryRateLimitStore();
        $a = new RateLimiter('bucket-a', maxRequests: 1, windowSeconds: 60, store: $store);
        $b = new RateLimiter('bucket-b', maxRequests: 1, windowSeconds: 60, store: $store);
        $ip = '1.1.1.1';

        $this->assertTrue($a->check($ip));
        $this->assertFalse($a->check($ip));
        // Otro bucket no se ve afectado
        $this->assertTrue($b->check($ip));
    }

    public function test_separates_ips(): void
    {
        $store = new InMemoryRateLimitStore();
        $limiter = new RateLimiter('test', maxRequests: 1, windowSeconds: 60, store: $store);

        $this->assertTrue($limiter->check('1.1.1.1'));
        $this->assertFalse($limiter->check('1.1.1.1'));
        // Otra IP arranca de cero
        $this->assertTrue($limiter->check('2.2.2.2'));
    }

    public function test_returns_true_when_store_throws(): void
    {
        // Store que siempre tira excepción — el limiter debe permitir (fail-open)
        $store = new class implements \TurneroYa\Services\RateLimitStore {
            public function incrementAndGet(string $bucket, string $ip, \DateTimeImmutable $windowStart): int
            {
                throw new \RuntimeException('db down');
            }
            public function purgeOlderThan(\DateTimeImmutable $olderThan): void {}
        };

        $limiter = new RateLimiter('test', maxRequests: 1, windowSeconds: 60, store: $store);
        $this->assertTrue($limiter->check('1.1.1.1'));
        $this->assertTrue($limiter->check('1.1.1.1'));
    }
}
