<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Schedule
{
    public static function forProfessional(string $professionalId): array
    {
        return Database::fetchAll(
            'SELECT * FROM schedules WHERE professional_id = :pid AND is_active = TRUE ORDER BY day_of_week ASC',
            ['pid' => $professionalId]
        );
    }

    public static function forBusiness(string $businessId): array
    {
        return Database::fetchAll(
            'SELECT * FROM schedules WHERE business_id = :b AND professional_id IS NULL AND is_active = TRUE ORDER BY day_of_week ASC',
            ['b' => $businessId]
        );
    }

    public static function replaceForProfessional(string $businessId, ?string $professionalId, array $schedules): void
    {
        Database::transaction(function () use ($businessId, $professionalId, $schedules) {
            if ($professionalId) {
                Database::query('DELETE FROM schedules WHERE professional_id = :pid', ['pid' => $professionalId]);
            } else {
                Database::query('DELETE FROM schedules WHERE business_id = :b AND professional_id IS NULL', ['b' => $businessId]);
            }
            foreach ($schedules as $s) {
                if (empty($s['is_active'])) continue;
                Database::insert('schedules', [
                    'day_of_week' => (int) $s['day_of_week'],
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                    'break_start' => $s['break_start'] ?? null,
                    'break_end' => $s['break_end'] ?? null,
                    'is_active' => true,
                    'professional_id' => $professionalId,
                    'business_id' => $businessId,
                ]);
            }
        });
    }

    public static function getScheduleForDay(string $businessId, ?string $professionalId, int $dayOfWeek): ?array
    {
        // Primero intenta con el horario del profesional
        if ($professionalId) {
            $row = Database::fetchOne(
                'SELECT * FROM schedules WHERE professional_id = :pid AND day_of_week = :d AND is_active = TRUE LIMIT 1',
                ['pid' => $professionalId, 'd' => $dayOfWeek]
            );
            if ($row) return $row;
        }
        // Fallback: horario del negocio
        return Database::fetchOne(
            'SELECT * FROM schedules WHERE business_id = :b AND professional_id IS NULL AND day_of_week = :d AND is_active = TRUE LIMIT 1',
            ['b' => $businessId, 'd' => $dayOfWeek]
        );
    }
}
