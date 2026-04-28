<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

final class CronRateLimit extends AbstractRateLimit
{
    protected function bucket(): string { return 'cron'; }
    protected function maxRequests(): int { return 10; }
    protected function windowSeconds(): int { return 60; }
}
