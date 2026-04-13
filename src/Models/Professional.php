<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Professional
{
    public static function allByBusiness(string $businessId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM professionals WHERE business_id = :b';
        if ($onlyActive) $sql .= ' AND is_active = TRUE';
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return Database::fetchAll($sql, ['b' => $businessId]);
    }

    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM professionals WHERE id = :id', ['id' => $id]);
    }

    public static function create(array $data): string
    {
        return Database::insert('professionals', $data);
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('professionals', $id, $data);
    }

    public static function delete(string $id): int
    {
        return Database::delete('professionals', $id);
    }

    public static function servicesForProfessional(string $professionalId): array
    {
        return Database::fetchAll(
            'SELECT s.*, ps.custom_duration, ps.custom_price
             FROM services s
             INNER JOIN professional_services ps ON ps.service_id = s.id
             WHERE ps.professional_id = :pid AND s.is_active = TRUE
             ORDER BY s.sort_order ASC, s.name ASC',
            ['pid' => $professionalId]
        );
    }

    public static function professionalsForService(string $serviceId): array
    {
        return Database::fetchAll(
            'SELECT p.*
             FROM professionals p
             INNER JOIN professional_services ps ON ps.professional_id = p.id
             WHERE ps.service_id = :sid AND p.is_active = TRUE
             ORDER BY p.sort_order ASC, p.name ASC',
            ['sid' => $serviceId]
        );
    }

    public static function syncServices(string $professionalId, array $serviceIds): void
    {
        Database::query('DELETE FROM professional_services WHERE professional_id = :pid', ['pid' => $professionalId]);
        foreach ($serviceIds as $sid) {
            Database::insert('professional_services', [
                'professional_id' => $professionalId,
                'service_id' => $sid,
            ]);
        }
    }
}
