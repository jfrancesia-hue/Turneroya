<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TurneroYa\Services\SlotCalculator;

/**
 * Tests adicionales de aritmética de slots — casos borde no cubiertos
 * por SlotArithmeticTest original.
 *
 * Se enfocan en:
 *  - servicios cuya duración no entra en el rango libre
 *  - bookings consecutivos que generan rangos pegados
 *  - breaks de cero minutos (start == end) que deberían ignorarse
 *  - alineación cuando el rango empieza en minuto no-cero
 *  - bookings exactamente al inicio o fin del rango
 */
final class SlotArithmeticAdditionalTest extends TestCase
{
    private SlotCalculator $calc;
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        $this->calc = new SlotCalculator('test-business-id');
        $this->tz = new DateTimeZone('America/Argentina/Buenos_Aires');
    }

    private function invoke(string $method, array $args): mixed
    {
        $ref = new ReflectionMethod($this->calc, $method);
        return $ref->invoke($this->calc, ...$args);
    }

    private function dt(string $iso): DateTimeImmutable
    {
        return new DateTimeImmutable($iso, $this->tz);
    }

    // ============================================================
    // Servicio que no entra en el rango libre
    // ============================================================

    public function test_service_longer_than_remaining_range_yields_no_slots(): void
    {
        // Rango libre 09:00–09:30 (30 min). Servicio de 60 min no entra.
        $rangeStart = $this->dt('2026-05-01 09:00');
        $rangeEnd = $this->dt('2026-05-01 09:30');

        $slotsInRange = $this->generateSlotsInRange($rangeStart, $rangeEnd, durationMin: 60, stepMin: 30);
        $this->assertEmpty($slotsInRange);
    }

    public function test_service_fits_exactly_in_range(): void
    {
        // Rango 09:00–10:00, servicio 60 min → exactamente un slot
        $rangeStart = $this->dt('2026-05-01 09:00');
        $rangeEnd = $this->dt('2026-05-01 10:00');

        $slots = $this->generateSlotsInRange($rangeStart, $rangeEnd, durationMin: 60, stepMin: 30);
        $this->assertCount(1, $slots);
        $this->assertEquals('09:00', $slots[0]->format('H:i'));
    }

    // ============================================================
    // Booking exactamente al inicio del rango
    // ============================================================

    public function test_booking_at_range_start_keeps_rest(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 09:00'),
            $this->dt('2026-05-01 10:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('10:00', $result[0][0]->format('H:i'));
        $this->assertEquals('12:00', $result[0][1]->format('H:i'));
    }

    public function test_booking_at_range_end_keeps_beginning(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 11:00'),
            $this->dt('2026-05-01 12:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('09:00', $result[0][0]->format('H:i'));
        $this->assertEquals('11:00', $result[0][1]->format('H:i'));
    }

    // ============================================================
    // Múltiples bookings consecutivos
    // ============================================================

    public function test_multiple_consecutive_bookings_partition_range(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 13:00')]];
        // Tres bookings consecutivos: 10–10:30, 10:30–11:00, 11:00–11:30
        $ranges = $this->invoke('subtractInterval', [
            $ranges, $this->dt('2026-05-01 10:00'), $this->dt('2026-05-01 10:30'),
        ]);
        $ranges = $this->invoke('subtractInterval', [
            $ranges, $this->dt('2026-05-01 10:30'), $this->dt('2026-05-01 11:00'),
        ]);
        $ranges = $this->invoke('subtractInterval', [
            $ranges, $this->dt('2026-05-01 11:00'), $this->dt('2026-05-01 11:30'),
        ]);

        // Esperado: [09:00–10:00] y [11:30–13:00]. La sustracción no fusiona,
        // así que el chunk del medio (10:30→11:00 sobre [10:30–13:00]) puede
        // dejar fragmentos vacíos, pero ninguno con duración positiva en 10:00–11:30.
        $totalMinutes = 0;
        foreach ($ranges as [$rs, $re]) {
            $totalMinutes += ($re->getTimestamp() - $rs->getTimestamp()) / 60;
            // Ningún rango debe solaparse con el bloque ocupado 10:00–11:30
            $this->assertTrue(
                $re <= $this->dt('2026-05-01 10:00') || $rs >= $this->dt('2026-05-01 11:30'),
                'No debe sobrevivir un rango que solape los bookings consecutivos'
            );
        }
        // Total: 60 min (09:00–10:00) + 90 min (11:30–13:00) = 150 min
        $this->assertSame(150, $totalMinutes);
    }

    // ============================================================
    // Break de cero minutos
    // ============================================================

    public function test_zero_minute_break_is_ignored(): void
    {
        $date = $this->dt('2026-05-01 00:00');
        $schedule = [
            'start_time' => '09:00', 'end_time' => '18:00',
            'break_start' => '13:00', 'break_end' => '13:00',
        ];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        // Break inválido → se ignora y queda un único rango
        $this->assertCount(1, $ranges);
        $this->assertEquals('09:00', $ranges[0][0]->format('H:i'));
        $this->assertEquals('18:00', $ranges[0][1]->format('H:i'));
    }

    public function test_inverted_break_is_ignored(): void
    {
        $date = $this->dt('2026-05-01 00:00');
        $schedule = [
            'start_time' => '09:00', 'end_time' => '18:00',
            'break_start' => '14:00', 'break_end' => '13:00', // invertido
        ];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        $this->assertCount(1, $ranges);
        $this->assertEquals('09:00', $ranges[0][0]->format('H:i'));
        $this->assertEquals('18:00', $ranges[0][1]->format('H:i'));
    }

    // ============================================================
    // Alineación con minuto no-cero de inicio
    // ============================================================

    public function test_align_when_range_starts_at_non_zero_minute(): void
    {
        // Si abren a las 09:13 y el step es 15 → primer slot a las 09:15
        $dt = $this->dt('2026-05-01 09:13');
        $result = $this->invoke('alignToStep', [$dt, 15]);
        $this->assertEquals('09:15', $result->format('H:i'));
    }

    public function test_align_when_range_starts_at_xx07_with_step_30(): void
    {
        $dt = $this->dt('2026-05-01 09:07');
        $result = $this->invoke('alignToStep', [$dt, 30]);
        $this->assertEquals('09:30', $result->format('H:i'));
    }

    public function test_align_when_range_starts_exactly_at_step_boundary(): void
    {
        $dt = $this->dt('2026-05-01 09:30');
        $result = $this->invoke('alignToStep', [$dt, 30]);
        $this->assertEquals('09:30', $result->format('H:i'));
    }

    // ============================================================
    // Helper: replica del bucle interno de generación de slots
    // ============================================================

    /**
     * @return DateTimeImmutable[] inicios de slot que caben en el rango.
     */
    private function generateSlotsInRange(
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
        int $durationMin,
        int $stepMin
    ): array {
        $cursor = $this->invoke('alignToStep', [$rangeStart, $stepMin]);
        $slots = [];
        while (true) {
            $end = $cursor->add(new DateInterval('PT' . $durationMin . 'M'));
            if ($end > $rangeEnd) break;
            $slots[] = $cursor;
            $cursor = $cursor->add(new DateInterval('PT' . $stepMin . 'M'));
        }
        return $slots;
    }
}
