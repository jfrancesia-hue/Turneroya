<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Business
{
    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM businesses WHERE id = :id', ['id' => $id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetchOne('SELECT * FROM businesses WHERE slug = :slug', ['slug' => $slug]);
    }

    public static function create(array $data): string
    {
        if (empty($data['slug'])) {
            $data['slug'] = self::uniqueSlug($data['name']);
        }
        return Database::insert('businesses', $data);
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('businesses', $id, $data);
    }

    public static function uniqueSlug(string $name): string
    {
        $base = slugify($name) ?: 'negocio';
        $slug = $base;
        $i = 1;
        while (Database::fetchColumn('SELECT 1 FROM businesses WHERE slug = :s', ['s' => $slug])) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public static function stats(string $businessId): array
    {
        return [
            'bookings_today' => (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM bookings WHERE business_id = :b AND date = CURRENT_DATE AND status NOT IN ('CANCELLED','NO_SHOW')",
                ['b' => $businessId]
            ),
            'bookings_week' => (int) Database::fetchColumn(
                "SELECT COUNT(*) FROM bookings WHERE business_id = :b AND date >= CURRENT_DATE AND date < CURRENT_DATE + INTERVAL '7 days' AND status NOT IN ('CANCELLED')",
                ['b' => $businessId]
            ),
            'clients_total' => (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM clients WHERE business_id = :b', ['b' => $businessId]
            ),
            'professionals_active' => (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM professionals WHERE business_id = :b AND is_active = TRUE', ['b' => $businessId]
            ),
        ];
    }
}
