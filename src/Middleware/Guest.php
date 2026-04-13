<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

use TurneroYa\Core\Auth as AuthService;

final class Guest
{
    public function handle(): void
    {
        if (AuthService::check()) {
            redirect('/dashboard');
        }
    }
}
