<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Booking
{
    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM bookings WHERE id = :id', ['id' => $id]);
    }

    public static function findWithRelations(string $id): ?array
    {
        return Database::fetchOne(
            'SELECT b.*,
                    c.name AS client_name, c.phone AS client_phone, c.whatsapp_number AS client_whatsapp, c.email AS client_email,
                    s.name AS service_name, s.duration AS service_duration, s.color AS service_color,
                    p.name AS professional_name, p.color AS professional_color
             FROM bookings b
             LEFT JOIN clients c ON c.id = b.client_id
             LEFT JOIN services s ON s.id = b.service_id
             LEFT JOIN professionals p ON p.id = b.professional_id
             WHERE b.id = :id',
            ['id' => $id]
        );
    }

    public static function forClient(string $businessId, string $clientId, int $limit = 200): array
    {
        $limit = max(1, min($limit, 1000));
        return Database::fetchAll(
            'SELECT b.*,
                    s.name AS service_name, s.color AS service_color, s.duration AS service_duration,
                    p.name AS professional_name, p.color AS professional_color
             FROM bookings b
             LEFT JOIN services s ON s.id = b.service_id
             LEFT JOIN professionals p ON p.id = b.professional_id
             WHERE b.business_id = :b AND b.client_id = :c
             ORDER BY b.date DESC, b.start_time DESC
             LIMIT ' . $limit,
            ['b' => $businessId, 'c' => $clientId]
        );
    }

    public static function forBusinessAndDateRange(string $businessId, string $from, string $to): array
    {
        return Database::fetchAll(
            'SELECT b.*,
                    c.name AS client_name, c.phone AS client_phone,
                    s.name AS service_name, s.color AS service_color, s.duration AS service_duration,
                    p.name AS professional_name, p.color AS professional_color
             FROM bookings b
             LEFT JOIN clients c ON c.id = b.client_id
             LEFT JOIN services s ON s.id = b.service_id
             LEFT JOIN professionals p ON p.id = b.professional_id
             WHERE b.business_id = :b AND b.date BETWEEN :from AND :to
             ORDER BY b.date ASC, b.start_time ASC',
            ['b' => $businessId, 'from' => $from, 'to' => $to]
        );
    }

    /**
     * Versión paginada del listado por rango. Devuelve rows + metadata.
     * Útil para vistas históricas donde el resultset puede ser grande.
     */
    public static function pagedForBusinessAndDateRange(
        string $businessId,
        string $from,
        string $to,
        int $page = 1,
        int $perPage = 50,
        string $order = 'ASC'
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 200));
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $offset = ($page - 1) * $perPage;

        $total = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM bookings
             WHERE business_id = :b AND date BETWEEN :from AND :to',
            ['b' => $businessId, 'from' => $from, 'to' => $to]
        );

        $rows = Database::fetchAll(
            'SELECT b.*,
                    c.name AS client_name, c.phone AS client_phone,
                    s.name AS service_name, s.color AS service_color, s.duration AS service_duration,
                    p.name AS professional_name, p.color AS professional_color
             FROM bookings b
             LEFT JOIN clients c ON c.id = b.client_id
             LEFT JOIN services s ON s.id = b.service_id
             LEFT JOIN professionals p ON p.id = b.professional_id
             WHERE b.business_id = :b AND b.date BETWEEN :from AND :to
             ORDER BY b.date ' . $order . ', b.start_time ' . $order . '
             LIMIT ' . $perPage . ' OFFSET ' . $offset,
            ['b' => $businessId, 'from' => $from, 'to' => $to]
        );

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function forDateAndProfessional(string $businessId, string $date, ?string $professionalId): array
    {
        $sql = "SELECT * FROM bookings
                WHERE business_id = :b
                  AND date = :date
                  AND status NOT IN ('CANCELLED','NO_SHOW','RESCHEDULED')";
        $params = ['b' => $businessId, 'date' => $date];
        if ($professionalId !== null) {
            $sql .= ' AND professional_id = :pid';
            $params['pid'] = $professionalId;
        }
        $sql .= ' ORDER BY start_time ASC';
        return Database::fetchAll($sql, $params);
    }

    public static function create(array $data): string
    {
        return Database::insert('bookings', $data);
    }

    public static function updateStatus(string $id, string $status): int
    {
        return Database::query(
            'UPDATE bookings SET status = :s, updated_at = NOW() WHERE id = :id',
            ['s' => $status, 'id' => $id]
        )->rowCount();
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('bookings', $id, $data);
    }

    public static function delete(string $id): int
    {
        return Database::delete('bookings', $id);
    }

    public static function pendingReminders(): array
    {
        // Bookings que necesitan recordatorio: el día siguiente, confirmation sin enviar, auto_reminder activo
        return Database::fetchAll(
            "SELECT b.*, c.whatsapp_number, c.phone, c.name AS client_name,
                    s.name AS service_name,
                    bz.name AS business_name, bz.reminder_hours_before, bz.whatsapp AS business_whatsapp
             FROM bookings b
             INNER JOIN clients c ON c.id = b.client_id
             INNER JOIN services s ON s.id = b.service_id
             INNER JOIN businesses bz ON bz.id = b.business_id
             WHERE bz.auto_reminder = TRUE
               AND b.reminder_sent = FALSE
               AND b.status IN ('PENDING','CONFIRMED')
               AND (b.date + b.start_time::time) BETWEEN NOW() AND NOW() + (bz.reminder_hours_before || ' hours')::interval"
        );
    }

    public static function markReminderSent(string $id): void
    {
        Database::query('UPDATE bookings SET reminder_sent = TRUE WHERE id = :id', ['id' => $id]);
    }

    public static function analytics(string $businessId, int $days = 30): array
    {
        $byDay = Database::fetchAll(
            "SELECT date::text AS day, COUNT(*) AS total,
                    COUNT(*) FILTER (WHERE status IN ('CONFIRMED','COMPLETED')) AS confirmed,
                    COUNT(*) FILTER (WHERE status = 'NO_SHOW') AS no_shows,
                    COUNT(*) FILTER (WHERE status = 'CANCELLED') AS cancelled
             FROM bookings
             WHERE business_id = :b AND date >= CURRENT_DATE - (:d || ' days')::interval
             GROUP BY date ORDER BY date ASC",
            ['b' => $businessId, 'd' => $days]
        );

        $topServices = Database::fetchAll(
            "SELECT s.name, COUNT(*) AS total
             FROM bookings b
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.business_id = :b AND b.date >= CURRENT_DATE - (:d || ' days')::interval
               AND b.status IN ('COMPLETED','CONFIRMED')
             GROUP BY s.id, s.name ORDER BY total DESC LIMIT 5",
            ['b' => $businessId, 'd' => $days]
        );

        $peakHours = Database::fetchAll(
            "SELECT SUBSTRING(start_time, 1, 2) AS hour, COUNT(*) AS total
             FROM bookings
             WHERE business_id = :b AND date >= CURRENT_DATE - (:d || ' days')::interval
               AND status IN ('COMPLETED','CONFIRMED')
             GROUP BY hour ORDER BY total DESC LIMIT 5",
            ['b' => $businessId, 'd' => $days]
        );

        $bySource = Database::fetchAll(
            "SELECT source, COUNT(*) AS total
             FROM bookings
             WHERE business_id = :b AND date >= CURRENT_DATE - (:d || ' days')::interval
             GROUP BY source ORDER BY total DESC",
            ['b' => $businessId, 'd' => $days]
        );

        return compact('byDay', 'topServices', 'peakHours', 'bySource');
    }
}
