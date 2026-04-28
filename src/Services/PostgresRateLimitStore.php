<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Core\Database;

/**
 * Store de rate limit respaldado por PostgreSQL.
 * Usa UPSERT (INSERT ... ON CONFLICT DO UPDATE) para atomicidad.
 */
final class PostgresRateLimitStore implements RateLimitStore
{
    public function incrementAndGet(string $bucket, string $ip, \DateTimeImmutable $windowStart): int
    {
        $sql = 'INSERT INTO rate_limits (bucket, ip, window_start, count)
                VALUES (:bucket, :ip, :window_start, 1)
                ON CONFLICT (bucket, ip, window_start)
                DO UPDATE SET count = rate_limits.count + 1
                RETURNING count';

        $row = Database::fetchOne($sql, [
            'bucket' => $bucket,
            'ip' => $ip,
            'window_start' => $windowStart->format('Y-m-d H:i:sP'),
        ]);

        return (int) ($row['count'] ?? 0);
    }

    public function purgeOlderThan(\DateTimeImmutable $olderThan): void
    {
        Database::query(
            'DELETE FROM rate_limits WHERE window_start < :cutoff',
            ['cutoff' => $olderThan->format('Y-m-d H:i:sP')]
        );
    }
}
