<?php
declare(strict_types=1);

namespace TurneroYa\Services;

/**
 * Store en memoria — uso exclusivo para tests.
 * No es thread-safe ni persistente.
 */
final class InMemoryRateLimitStore implements RateLimitStore
{
    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<string, \DateTimeImmutable> */
    private array $windows = [];

    public function incrementAndGet(string $bucket, string $ip, \DateTimeImmutable $windowStart): int
    {
        $key = $bucket . '|' . $ip . '|' . $windowStart->format('U');
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
        $this->windows[$key] = $windowStart;
        return $this->counts[$key];
    }

    public function purgeOlderThan(\DateTimeImmutable $olderThan): void
    {
        foreach ($this->windows as $key => $w) {
            if ($w < $olderThan) {
                unset($this->counts[$key], $this->windows[$key]);
            }
        }
    }
}
