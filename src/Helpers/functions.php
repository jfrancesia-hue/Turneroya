<?php
/**
 * Helpers globales de TurneroYa.
 */
declare(strict_types=1);

use TurneroYa\Core\App;
use TurneroYa\Core\View;
use TurneroYa\Core\Session;
use TurneroYa\Core\Config;

if (!function_exists('app')) {
    function app(): App { return App::getInstance(); }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed {
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $systemValue = getenv($key);
        $value = $systemValue !== false ? $systemValue : ($_ENV[$key] ?? null);
        if ($value === false || $value === null) return $default;
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string {
        return View::render($template, $data);
    }
}

if (!function_exists('render')) {
    function render(string $template, array $data = []): void {
        echo View::render($template, $data);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $code = 302): never {
        header('Location: ' . $url, true, $code);
        exit;
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): never {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '') {
            $refHost = parse_url($ref, PHP_URL_HOST);
            $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: '';
            $appHost = $appUrl !== '' ? parse_url($appUrl, PHP_URL_HOST) : ($_SERVER['HTTP_HOST'] ?? '');
            if ($refHost !== null && $appHost !== '' && strcasecmp($refHost, (string) $appHost) === 0) {
                redirect($ref);
            }
        }
        redirect($fallback);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed {
        $old = Session::get('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim((string) config('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('auth')) {
    function auth(): ?array {
        return Session::get('user');
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?string {
        return Session::get('user')['id'] ?? null;
    }
}

if (!function_exists('business_id')) {
    function business_id(): ?string {
        return Session::get('user')['business_id'] ?? null;
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $code = 200): never {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never {
        echo '<pre style="background:#0f172a;color:#a7f3d0;padding:20px;font-family:monospace;font-size:13px;">';
        foreach ($vars as $v) { var_dump($v); echo "\n"; }
        echo '</pre>';
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void {
        Session::flash($type, $message);
    }
}

if (!function_exists('now_tz')) {
    function now_tz(): \DateTimeImmutable {
        return new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'America/Argentina/Buenos_Aires')));
    }
}

if (!function_exists('format_money')) {
    function format_money(int|float|string|null $amount, string $currency = 'ARS'): string {
        if ($amount === null) return '-';
        return $currency . ' ' . number_format((float) $amount, 2, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(string|\DateTimeInterface $date, string $format = 'd/m/Y H:i'): string {
        if (is_string($date)) $date = new \DateTimeImmutable($date);
        return $date->format($format);
    }
}

if (!function_exists('uuid')) {
    function uuid(): string {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $text) ?? '');
        return trim($text, '-');
    }
}
