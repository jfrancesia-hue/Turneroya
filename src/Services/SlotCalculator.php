<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use TurneroYa\Models\Business;
use TurneroYa\Models\Service;
use TurneroYa\Models\Professional;
use TurneroYa\Models\Schedule;
use TurneroYa\Models\Blockout;
use TurneroYa\Models\Booking;

/**
 * SlotCalculator — la lógica MÁS CRÍTICA del sistema.
 *
 * Genera slots disponibles para una fecha dada considerando:
 *  1. Horario del profesional (o del negocio si no hay profesional)
 *  2. Duración del servicio
 *  3. Breaks (pausas del horario)
 *  4. Blockouts (vacaciones, feriados)
 *  5. Bookings existentes (no doble-reservar)
 *  6. minAdvanceHours (no mostrar slots demasiado cercanos)
 *  7. maxAdvanceDays (no mostrar slots muy lejanos)
 *  8. Que el slot + duración quepa antes del siguiente evento (booking, break, cierre)
 *
 * Zona horaria: siempre America/Argentina/Buenos_Aires
 */
final class SlotCalculator
{
    private DateTimeZone $tz;

    public function __construct(private readonly string $businessId)
    {
        $this->tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    }

    /**
     * @return array<int, array{start: string, end: string, label: string, iso: string}>
     */
    public function getAvailableSlots(string $date, string $serviceId, ?string $professionalId = null): array
    {
        $business = Business::find($this->businessId);
        if (!$business) return [];

        $service = Service::find($serviceId);
        if (!$service || (string) $service['business_id'] !== $this->businessId) return [];

        // Validar rango de fechas permitido
        $targetDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->tz);
        if (!$targetDate) return [];
        $today = new DateTimeImmutable('today', $this->tz);
        $maxDate = $today->modify('+' . (int) $business['max_advance_days'] . ' days');
        if ($targetDate < $today || $targetDate > $maxDate) return [];

        $duration = (int) $service['duration']; // minutos
        $slotStep = (int) $business['slot_duration']; // minutos entre inicios posibles

        // Si no se indica profesional, intentar con todos los que hacen ese servicio
        // y retornar unión de slots. Pero lo más seguro: exigir profesional o
        // tomar el primero disponible del día. Para el frontend pedimos siempre
        // profesional concreto; para bot, puede ser null y buscaremos primer disponible.
        if ($professionalId === null) {
            return $this->getSlotsAcrossProfessionals($business, $service, $date, $duration);
        }

        return $this->computeSlotsForProfessional($business, $service, $professionalId, $date, $duration, $slotStep);
    }

    /**
     * Cuando no hay profesional especificado, buscamos slots en cualquier profesional
     * que haga el servicio y los unificamos (sin duplicados de horario).
     */
    private function getSlotsAcrossProfessionals(array $business, array $service, string $date, int $duration): array
    {
        $pros = Professional::professionalsForService($service['id']);
        if (!$pros) return [];

        $unified = [];
        foreach ($pros as $pro) {
            $slots = $this->computeSlotsForProfessional(
                $business,
                $service,
                $pro['id'],
                $date,
                $duration,
                (int) $business['slot_duration']
            );
            foreach ($slots as $s) {
                // Si dos profesionales tienen el mismo horario libre, gana el primero
                // que lo reportó (UX: presentamos un único slot por hora).
                if (!isset($unified[$s['start']])) {
                    $unified[$s['start']] = [
                        ...$s,
                        'professional_id' => $pro['id'],
                        'professional_name' => $pro['name'],
                    ];
                }
            }
        }
        ksort($unified);
        return array_values($unified);
    }

    private function computeSlotsForProfessional(
        array $business,
        array $service,
        string $professionalId,
        string $date,
        int $duration,
        int $slotStep
    ): array {
        $targetDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->tz);
        if (!$targetDate) return [];

        $dayOfWeek = (int) $targetDate->format('w'); // 0=dom, 6=sáb

        // 1) Obtener schedule del día
        $schedule = Schedule::getScheduleForDay($this->businessId, $professionalId, $dayOfWeek);
        if (!$schedule) return []; // sin horario ese día → cerrado

        // 2) Construir rangos de trabajo (considerando break)
        $workRanges = $this->buildWorkRanges($targetDate, $schedule);
        if (empty($workRanges)) return [];

        // 3) Sustraer blockouts del día
        $blockouts = Blockout::forDate($this->businessId, $professionalId, $date);
        $workRanges = $this->subtractBlockouts($workRanges, $blockouts);
        if (empty($workRanges)) return [];

        // 4) Sustraer bookings existentes
        $bookings = Booking::forDateAndProfessional($this->businessId, $date, $professionalId);
        $workRanges = $this->subtractBookings($workRanges, $bookings, $targetDate);
        if (empty($workRanges)) return [];

        // 5) Generar slots dentro de cada rango libre
        $now = new DateTimeImmutable('now', $this->tz);
        $minAdvance = (int) $business['min_advance_hours'];
        $earliestAllowed = $now->add(new DateInterval('PT' . $minAdvance . 'H'));

        $slots = [];
        foreach ($workRanges as $range) {
            [$rangeStart, $rangeEnd] = $range;

            // Alinear al siguiente múltiplo de slotStep
            $cursor = $this->alignToStep($rangeStart, $slotStep);
            while (true) {
                $slotEnd = $cursor->add(new DateInterval('PT' . $duration . 'M'));
                if ($slotEnd > $rangeEnd) break; // no cabe el servicio

                if ($cursor >= $earliestAllowed) {
                    $slots[] = [
                        'start' => $cursor->format('H:i'),
                        'end' => $slotEnd->format('H:i'),
                        'label' => $cursor->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                        'iso' => $cursor->format('c'),
                    ];
                }
                $cursor = $cursor->add(new DateInterval('PT' . $slotStep . 'M'));
            }
        }

        return $slots;
    }

    /**
     * Construye rangos horarios de trabajo considerando breaks.
     * @return array<int, array{0: DateTimeImmutable, 1: DateTimeImmutable}>
     */
    private function buildWorkRanges(DateTimeImmutable $date, array $schedule): array
    {
        $start = $this->dateWithTime($date, $schedule['start_time']);
        $end = $this->dateWithTime($date, $schedule['end_time']);
        if (!$start || !$end || $end <= $start) return [];

        $hasBreak = !empty($schedule['break_start']) && !empty($schedule['break_end']);
        if (!$hasBreak) {
            return [[$start, $end]];
        }

        $breakStart = $this->dateWithTime($date, $schedule['break_start']);
        $breakEnd = $this->dateWithTime($date, $schedule['break_end']);
        if (!$breakStart || !$breakEnd || $breakEnd <= $breakStart) {
            return [[$start, $end]];
        }

        $ranges = [];
        if ($breakStart > $start) $ranges[] = [$start, $breakStart];
        if ($end > $breakEnd) $ranges[] = [$breakEnd, $end];
        return $ranges;
    }

    /**
     * Dado una fecha y un string HH:MM, devuelve DateTimeImmutable combinado.
     */
    private function dateWithTime(DateTimeImmutable $date, string $hhmm): ?DateTimeImmutable
    {
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) return null;
        return $date->setTime((int) $m[1], (int) $m[2], 0);
    }

    /**
     * Alinea $dt hacia arriba al próximo múltiplo de $stepMinutes.
     */
    private function alignToStep(DateTimeImmutable $dt, int $stepMinutes): DateTimeImmutable
    {
        $minutes = (int) $dt->format('i');
        $remainder = $minutes % $stepMinutes;
        if ($remainder === 0) {
            return $dt->setTime((int) $dt->format('H'), $minutes, 0);
        }
        $delta = $stepMinutes - $remainder;
        $aligned = $dt->add(new DateInterval('PT' . $delta . 'M'));
        return $aligned->setTime(
            (int) $aligned->format('H'),
            (int) $aligned->format('i'),
            0
        );
    }

    /**
     * Sustrae los rangos de blockouts a los rangos de trabajo.
     */
    private function subtractBlockouts(array $ranges, array $blockouts): array
    {
        foreach ($blockouts as $b) {
            $bStart = new DateTimeImmutable($b['start_date'], $this->tz);
            $bEnd = new DateTimeImmutable($b['end_date'], $this->tz);
            $ranges = $this->subtractInterval($ranges, $bStart, $bEnd);
        }
        return $ranges;
    }

    /**
     * Sustrae los horarios de bookings existentes (que ya bloquean slots).
     */
    private function subtractBookings(array $ranges, array $bookings, DateTimeImmutable $targetDate): array
    {
        foreach ($bookings as $b) {
            $bStart = $this->dateWithTime($targetDate, $b['start_time']);
            $bEnd = $this->dateWithTime($targetDate, $b['end_time']);
            if (!$bStart || !$bEnd) continue;
            $ranges = $this->subtractInterval($ranges, $bStart, $bEnd);
        }
        return $ranges;
    }

    /**
     * Sustrae un único intervalo [from, to] de una lista de rangos.
     */
    private function subtractInterval(array $ranges, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $result = [];
        foreach ($ranges as [$rs, $re]) {
            // Sin solapamiento → se mantiene
            if ($to <= $rs || $from >= $re) {
                $result[] = [$rs, $re];
                continue;
            }
            // Cubre todo el rango → se elimina
            if ($from <= $rs && $to >= $re) {
                continue;
            }
            // Corta al comienzo
            if ($from <= $rs && $to < $re) {
                $result[] = [$to, $re];
                continue;
            }
            // Corta al final
            if ($from > $rs && $to >= $re) {
                $result[] = [$rs, $from];
                continue;
            }
            // Corta en el medio → queda partido en dos
            $result[] = [$rs, $from];
            $result[] = [$to, $re];
        }
        return $result;
    }

    /**
     * Verifica si un slot específico sigue disponible (idempotencia contra doble-booking).
     */
    public function isSlotAvailable(string $date, string $startTime, int $duration, string $professionalId): bool
    {
        $business = Business::find($this->businessId);
        if (!$business) return false;
        $targetDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->tz);
        if (!$targetDate) return false;

        $dayOfWeek = (int) $targetDate->format('w');
        $schedule = Schedule::getScheduleForDay($this->businessId, $professionalId, $dayOfWeek);
        if (!$schedule) return false;

        $start = $this->dateWithTime($targetDate, $startTime);
        if (!$start) return false;
        $end = $start->add(new DateInterval('PT' . $duration . 'M'));

        // Chequeo de horario
        $openStart = $this->dateWithTime($targetDate, $schedule['start_time']);
        $openEnd = $this->dateWithTime($targetDate, $schedule['end_time']);
        if (!$openStart || !$openEnd || $start < $openStart || $end > $openEnd) return false;

        // Break
        if (!empty($schedule['break_start']) && !empty($schedule['break_end'])) {
            $breakStart = $this->dateWithTime($targetDate, $schedule['break_start']);
            $breakEnd = $this->dateWithTime($targetDate, $schedule['break_end']);
            if ($breakStart && $breakEnd && !($end <= $breakStart || $start >= $breakEnd)) {
                return false;
            }
        }

        // Blockouts
        $blockouts = Blockout::forDate($this->businessId, $professionalId, $date);
        foreach ($blockouts as $b) {
            $bStart = new DateTimeImmutable($b['start_date'], $this->tz);
            $bEnd = new DateTimeImmutable($b['end_date'], $this->tz);
            if (!($end <= $bStart || $start >= $bEnd)) return false;
        }

        // Bookings existentes
        $bookings = Booking::forDateAndProfessional($this->businessId, $date, $professionalId);
        foreach ($bookings as $b) {
            $bStart = $this->dateWithTime($targetDate, $b['start_time']);
            $bEnd = $this->dateWithTime($targetDate, $b['end_time']);
            if (!$bStart || !$bEnd) continue;
            if (!($end <= $bStart || $start >= $bEnd)) return false;
        }

        // Mínimo de anticipación
        $now = new DateTimeImmutable('now', $this->tz);
        $minAdvance = (int) $business['min_advance_hours'];
        if ($start < $now->add(new DateInterval('PT' . $minAdvance . 'H'))) return false;

        return true;
    }

    /**
     * Busca los próximos N slots disponibles a partir de hoy (útil para el bot).
     * @return array<int, array{date: string, start: string, end: string, professional_id: ?string, professional_name: ?string}>
     */
    public function nextAvailableSlots(string $serviceId, ?string $professionalId, int $limit = 5): array
    {
        $business = Business::find($this->businessId);
        if (!$business) return [];
        $maxDays = (int) $business['max_advance_days'];

        $found = [];
        $cursor = new DateTimeImmutable('today', $this->tz);
        for ($i = 0; $i < $maxDays && count($found) < $limit; $i++) {
            $date = $cursor->format('Y-m-d');
            $slots = $this->getAvailableSlots($date, $serviceId, $professionalId);
            foreach ($slots as $s) {
                $found[] = [
                    'date' => $date,
                    'day_name' => self::dayName((int) $cursor->format('w')),
                    'start' => $s['start'],
                    'end' => $s['end'],
                    'professional_id' => $s['professional_id'] ?? $professionalId,
                    'professional_name' => $s['professional_name'] ?? null,
                ];
                if (count($found) >= $limit) break;
            }
            $cursor = $cursor->modify('+1 day');
        }
        return $found;
    }

    private static function dayName(int $dow): string
    {
        return ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'][$dow] ?? '';
    }
}
