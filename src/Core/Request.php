<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Wrapper sobre $_GET/$_POST/php://input.
 */
final class Request
{
    public static function method(): string
    {
        $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($m === 'POST' && isset($_POST['_method'])) return strtoupper($_POST['_method']);
        return $m;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function all(): array
    {
        $json = self::json();
        return array_merge($_GET, $_POST, $json);
    }

    public static function json(): array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($ct, 'application/json')) return [];
        $body = file_get_contents('php://input') ?: '';
        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public static function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public static function ip(): string
    {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $first = trim(explode(',', $xff)[0]);
            if ($first !== '') return $first;
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }
}
