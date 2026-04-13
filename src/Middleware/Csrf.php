<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

use TurneroYa\Core\Request;
use TurneroYa\Core\Session;

final class Csrf
{
    public function handle(): void
    {
        $method = Request::method();
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) return;
        // Excluir webhooks externos
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/api/webhook/')) return;
        $token = $_POST['_csrf'] ?? Request::header('X-CSRF-Token');
        if (!Session::verifyCsrf($token)) {
            http_response_code(419);
            echo 'CSRF token inválido o expirado';
            exit;
        }
    }
}
