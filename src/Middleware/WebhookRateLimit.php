<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

final class WebhookRateLimit extends AbstractRateLimit
{
    protected function bucket(): string { return 'webhook'; }
    protected function maxRequests(): int { return 60; }
    protected function windowSeconds(): int { return 60; }
}
