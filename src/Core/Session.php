<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Manejo de sesiones con CSRF y flash messages.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $savePath = BASE_PATH . '/storage/sessions';
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => self::isHttps(),
        ]);
        session_name('turneroya_sid');
        session_start();

        if (!isset($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        // Procesar flash de esta request
        if (isset($_SESSION['_flash_new'])) {
            $_SESSION['_flash'] = $_SESSION['_flash_new'];
            unset($_SESSION['_flash_new']);
        } else {
            unset($_SESSION['_flash']);
        }
    }

    private static function isHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strcasecmp($proto, 'https') === 0) return true;
        return ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash_new'][$type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        return $_SESSION['_flash'][$type] ?? null;
    }

    public static function allFlash(): array
    {
        return $_SESSION['_flash'] ?? [];
    }

    public static function csrfToken(): string
    {
        return (string) ($_SESSION['_csrf'] ?? '');
    }

    public static function verifyCsrf(?string $token): bool
    {
        if (!$token) return false;
        return hash_equals((string) ($_SESSION['_csrf'] ?? ''), $token);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function flashOldInput(array $input): void
    {
        $_SESSION['_flash_new']['_old_input'] = $input;
    }
}
