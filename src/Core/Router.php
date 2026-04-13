<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Router simple con soporte de grupos, middlewares y parámetros.
 * Formato de ruta: /book/{slug}, /api/bookings/{id}
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:mixed, middleware:array<int,string>}> */
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, mixed $handler): self { return $this->add('GET', $path, $handler); }
    public function post(string $path, mixed $handler): self { return $this->add('POST', $path, $handler); }
    public function put(string $path, mixed $handler): self { return $this->add('PUT', $path, $handler); }
    public function patch(string $path, mixed $handler): self { return $this->add('PATCH', $path, $handler); }
    public function delete(string $path, mixed $handler): self { return $this->add('DELETE', $path, $handler); }

    public function group(array $attrs, callable $callback): void
    {
        $this->groupStack[] = $attrs;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, mixed $handler): self
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $g) {
            if (isset($g['prefix'])) $prefix .= '/' . trim($g['prefix'], '/');
            if (isset($g['middleware'])) {
                $middleware = array_merge($middleware, (array) $g['middleware']);
            }
        }
        $fullPath = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') $fullPath = '/';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $fullPath,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Soportar _method override para PUT/DELETE via form
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = '/' . trim($uri, '/');
        if ($uri === '') $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $params = $this->matches($route['pattern'], $uri);
            if ($params !== null) {
                $this->runMiddleware($route['middleware']);
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        echo view('errors/404');
    }

    private function matches(string $pattern, string $uri): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!preg_match('#^' . $regex . '$#', $uri, $m)) return null;
        $params = [];
        foreach ($m as $k => $v) {
            if (!is_int($k)) $params[$k] = $v;
        }
        return $params;
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $mw) {
            $class = 'TurneroYa\\Middleware\\' . $mw;
            if (class_exists($class)) {
                (new $class())->handle();
            }
        }
    }

    private function callHandler(mixed $handler, array $params): void
    {
        if (is_callable($handler)) {
            echo $handler($params) ?? '';
            return;
        }
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            $fqcn = 'TurneroYa\\Controllers\\' . $class;
            if (!class_exists($fqcn)) {
                throw new \RuntimeException("Controlador no encontrado: $fqcn");
            }
            $controller = new $fqcn();
            echo $controller->$method($params) ?? '';
            return;
        }
        throw new \RuntimeException('Handler de ruta inválido');
    }
}
