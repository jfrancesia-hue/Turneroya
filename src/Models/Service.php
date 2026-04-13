<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Service
{
    public static function allByBusiness(string $businessId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM services WHERE business_id = :b';
        if ($onlyActive) $sql .= ' AND is_active = TRUE';
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Database::fetchAll($sql, ['b' => $businessId]);
    }

    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM services WHERE id = :id', ['id' => $id]);
    }

    public static function create(array $data): string
    {
        return Database::insert('services', $data);
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('services', $id, $data);
    }

    public static function delete(string $id): int
    {
        return Database::delete('services', $id);
    }
}
