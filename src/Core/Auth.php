<?php
declare(strict_types=1);

namespace TurneroYa\Core;

use TurneroYa\Models\User;

/**
 * Autenticación de usuarios (sesiones + password_hash).
 */
final class Auth
{
    public static function attempt(string $email, string $password): ?array
    {
        $user = User::findByEmail($email);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        self::login($user);
        return $user;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user', [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'business_id' => $user['business_id'] ?? null,
        ]);
    }

    public static function logout(): void
    {
        Session::destroy();
        Session::start();
    }

    public static function check(): bool
    {
        return Session::has('user');
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function id(): ?string
    {
        return Session::get('user')['id'] ?? null;
    }

    public static function businessId(): ?string
    {
        return Session::get('user')['business_id'] ?? null;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function refresh(): void
    {
        $id = self::id();
        if (!$id) return;
        $user = User::find($id);
        if ($user) self::login($user);
    }
}
