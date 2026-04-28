<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

final class PublicBookingRateLimit extends AbstractRateLimit
{
    protected function bucket(): string { return 'public_booking'; }
    protected function maxRequests(): int { return 30; }
    protected function windowSeconds(): int { return 60; }
}
