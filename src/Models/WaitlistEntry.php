<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;
use TurneroYa\Services\WaitlistMatcher;

/**
 * Entries de lista de espera. Un cliente entra acá cuando los horarios
 * que quería estaban ocupados; cuando se libera un slot que matchee
 * (mismo servicio + profesional compatible + fecha/hora dentro del rango),
 * se notifica automáticamente por WhatsApp.
 */
final class WaitlistEntry
{
    public static function create(array $data): string
    {
        return Database::insert('waitlist_entries', $data);
    }

    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM waitlist_entries WHERE id = :id', ['id' => $id]);
    }

    public static function forBusiness(string $businessId, string $status = 'PENDING'): array
    {
        return Database::fetchAll(
            'SELECT * FROM waitlist_entries
             WHERE business_id = :b AND status = :s
             ORDER BY created_at ASC',
            ['b' => $businessId, 's' => $status]
        );
    }

    /**
     * Devuelve la primera entry PENDING (FIFO por created_at) que matchee con
     * el slot recién liberado. Filtra primero en SQL con un rango amplio (usa
     * el índice idx_waitlist_match) y aplica los rangos exactos de hora con
     * WaitlistMatcher en PHP.
     */
    public static function findMatchingEntry(
        string $businessId,
        string $serviceId,
        ?string $professionalId,
        string $date,
        string $startTime
    ): ?array {
        $candidates = Database::fetchAll(
            "SELECT * FROM waitlist_entries
             WHERE business_id = :biz
               AND service_id = :sid
               AND status = 'PENDING'
               AND (professional_id IS NULL OR professional_id = :pid)
               AND preferred_date_from <= :date
               AND (preferred_date_to IS NULL OR preferred_date_to >= :date)
             ORDER BY created_at ASC",
            [
                'biz' => $businessId,
                'sid' => $serviceId,
                // Si professional_id es null pasamos string vacío: el OR de arriba
                // ya lo cubre con "professional_id IS NULL".
                'pid' => $professionalId ?? '',
                'date' => $date,
            ]
        );

        foreach ($candidates as $entry) {
            if (WaitlistMatcher::matches($entry, $serviceId, $professionalId, $date, $startTime)) {
                return $entry;
            }
        }
        return null;
    }

    public static function markNotified(string $id, ?string $bookingHintId = null): void
    {
        Database::query(
            "UPDATE waitlist_entries
                SET status = 'NOTIFIED',
                    notified_at = NOW(),
                    notified_booking_id = :bid,
                    updated_at = NOW()
              WHERE id = :id",
            ['id' => $id, 'bid' => $bookingHintId]
        );
    }

    public static function markConverted(string $id): void
    {
        Database::query(
            "UPDATE waitlist_entries
                SET status = 'CONVERTED',
                    converted_at = NOW(),
                    updated_at = NOW()
              WHERE id = :id",
            ['id' => $id]
        );
    }

    public static function markExpired(string $id): void
    {
        Database::query(
            "UPDATE waitlist_entries SET status = 'EXPIRED', updated_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }

    public static function cancel(string $id): void
    {
        Database::query(
            "UPDATE waitlist_entries SET status = 'CANCELLED', updated_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }
}
