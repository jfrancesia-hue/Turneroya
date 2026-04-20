<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Plan
{
    public static function find(string $id): ?array
    {
        $row = Database::fetchOne('SELECT * FROM plans WHERE id = :id', ['id' => $id]);
        return $row ? self::hydrate($row) : null;
    }

    public static function allActive(): array
    {
        $rows = Database::fetchAll(
            'SELECT * FROM plans WHERE is_active = TRUE ORDER BY sort_order ASC, price_monthly ASC'
        );
        return array_map([self::class, 'hydrate'], $rows);
    }

    public static function findFree(): ?array
    {
        return self::find('FREE');
    }

    /**
     * Decodifica features_json y normaliza booleans de Postgres.
     */
    private static function hydrate(array $row): array
    {
        if (isset($row['features_json']) && is_string($row['features_json'])) {
            $decoded = json_decode($row['features_json'], true);
            $row['features'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['features'] = [];
        }

        foreach ([
            'has_whatsapp_bot','has_advanced_analytics','has_public_booking','has_reminders',
            'has_deposits','has_custom_branding','has_api_access','has_multi_location',
            'has_priority_support','is_featured','is_active',
        ] as $k) {
            if (array_key_exists($k, $row)) {
                $row[$k] = (bool) $row[$k];
            }
        }
        return $row;
    }
}
