<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

use TurneroYa\Core\Auth as AuthService;

final class Auth
{
    public function handle(): void
    {
        if (!AuthService::check()) {
            flash('error', 'Debés iniciar sesión para acceder');
            redirect('/login');
        }
    }
}
