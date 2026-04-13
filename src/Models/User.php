<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class User
{
    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE id = :id', ['id' => $id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM users WHERE LOWER(email) = LOWER(:email)', ['email' => $email]);
    }

    public static function create(array $data): string
    {
        return Database::insert('users', $data);
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('users', $id, $data);
    }

    public static function attachBusiness(string $userId, string $businessId): void
    {
        Database::query('UPDATE users SET business_id = :bid, updated_at = NOW() WHERE id = :id',
            ['bid' => $businessId, 'id' => $userId]);
    }
}
