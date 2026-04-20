<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

/**
 * Contadores de uso por ciclo mensual (1ro de mes → fin de mes).
 * Se crean on-demand la primera vez que se incrementa un contador en el período.
 */
final class UsageCounter
{
    public static function currentFor(string $businessId): array
    {
        [$start, $end] = self::currentPeriod();

        $row = Database::fetchOne(
            'SELECT * FROM usage_counters WHERE business_id = :b AND period_start = :s',
            ['b' => $businessId, 's' => $start]
        );

        if ($row) return $row;

        $id = Database::insert('usage_counters', [
            'business_id' => $businessId,
            'period_start' => $start,
            'period_end' => $end,
        ]);

        return Database::fetchOne('SELECT * FROM usage_counters WHERE id = :id', ['id' => $id]) ?? [
            'bookings_count' => 0,
            'bot_messages_count' => 0,
            'reminders_sent_count' => 0,
        ];
    }

    public static function incrementBookings(string $businessId, int $by = 1): void
    {
        self::increment($businessId, 'bookings_count', $by);
    }

    public static function incrementBotMessages(string $businessId, int $by = 1): void
    {
        self::increment($businessId, 'bot_messages_count', $by);
    }

    public static function incrementReminders(string $businessId, int $by = 1): void
    {
        self::increment($businessId, 'reminders_sent_count', $by);
    }

    private static function increment(string $businessId, string $column, int $by): void
    {
        // Asegura que la fila exista
        self::currentFor($businessId);
        [$start] = self::currentPeriod();

        Database::query(
            "UPDATE usage_counters
             SET $column = $column + :by,
                 updated_at = NOW()
             WHERE business_id = :b AND period_start = :s",
            ['by' => $by, 'b' => $businessId, 's' => $start]
        );
    }

    /**
     * @return array{0: string, 1: string} [period_start, period_end] en Y-m-d
     */
    private static function currentPeriod(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $start = $now->format('Y-m-01');
        $end = $now->format('Y-m-t');
        return [$start, $end];
    }
}
