<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Blockout
{
    public static function forBusiness(string $businessId): array
    {
        return Database::fetchAll(
            'SELECT * FROM blockouts WHERE business_id = :b ORDER BY start_date DESC',
            ['b' => $businessId]
        );
    }

    public static function forDate(string $businessId, ?string $professionalId, string $date): array
    {
        $sql = "SELECT * FROM blockouts
                WHERE business_id = :b
                  AND :date::date BETWEEN start_date::date AND end_date::date
                  AND (professional_id IS NULL OR professional_id = :pid)";
        return Database::fetchAll($sql, [
            'b' => $businessId,
            'date' => $date,
            'pid' => $professionalId,
        ]);
    }

    public static function create(array $data): string
    {
        return Database::insert('blockouts', $data);
    }

    public static function delete(string $id): int
    {
        return Database::delete('blockouts', $id);
    }
}
