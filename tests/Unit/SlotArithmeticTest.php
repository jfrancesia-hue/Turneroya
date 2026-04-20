<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TurneroYa\Services\SlotCalculator;

/**
 * Tests de la aritmética interna del SlotCalculator.
 * Se accede a métodos privados via Reflection porque esta lógica es pura
 * (no toca DB) y no queremos exponerla en la API pública.
 *
 * La cobertura de los métodos públicos (getAvailableSlots, isSlotAvailable)
 * requiere tests de integración con DB — se agregan aparte.
 */
final class SlotArithmeticTest extends TestCase
{
    private SlotCalculator $calc;
    private DateTimeZone $tz;

    protected function setUp(): void
    {
        // El businessId no importa para los métodos que testeamos aquí
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
    // subtractInterval
    // ============================================================

    public function test_subtract_interval_no_overlap_keeps_range(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 14:00'),
            $this->dt('2026-05-01 16:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('09:00', $result[0][0]->format('H:i'));
        $this->assertEquals('12:00', $result[0][1]->format('H:i'));
    }

    public function test_subtract_interval_full_cover_removes_range(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 08:00'),
            $this->dt('2026-05-01 13:00'),
        ]);
        $this->assertCount(0, $result);
    }

    public function test_subtract_interval_cuts_beginning(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 08:00'),
            $this->dt('2026-05-01 10:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('10:00', $result[0][0]->format('H:i'));
        $this->assertEquals('12:00', $result[0][1]->format('H:i'));
    }

    public function test_subtract_interval_cuts_end(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 11:00'),
            $this->dt('2026-05-01 13:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('09:00', $result[0][0]->format('H:i'));
        $this->assertEquals('11:00', $result[0][1]->format('H:i'));
    }

    public function test_subtract_interval_splits_middle(): void
    {
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 10:00'),
            $this->dt('2026-05-01 11:00'),
        ]);
        $this->assertCount(2, $result);
        $this->assertEquals('09:00', $result[0][0]->format('H:i'));
        $this->assertEquals('10:00', $result[0][1]->format('H:i'));
        $this->assertEquals('11:00', $result[1][0]->format('H:i'));
        $this->assertEquals('12:00', $result[1][1]->format('H:i'));
    }

    public function test_subtract_interval_multiple_ranges_preserves_independent(): void
    {
        $ranges = [
            [$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')],
            [$this->dt('2026-05-01 14:00'), $this->dt('2026-05-01 18:00')],
        ];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 15:00'),
            $this->dt('2026-05-01 16:00'),
        ]);
        $this->assertCount(3, $result);
        // El primer rango sobrevive intacto
        $this->assertEquals('09:00', $result[0][0]->format('H:i'));
        $this->assertEquals('12:00', $result[0][1]->format('H:i'));
        // El segundo rango queda partido
        $this->assertEquals('14:00', $result[1][0]->format('H:i'));
        $this->assertEquals('15:00', $result[1][1]->format('H:i'));
        $this->assertEquals('16:00', $result[2][0]->format('H:i'));
        $this->assertEquals('18:00', $result[2][1]->format('H:i'));
    }

    public function test_subtract_interval_adjacent_boundary_keeps_range(): void
    {
        // Booking justo al final del rango (termina exactamente cuando empieza el siguiente)
        $ranges = [[$this->dt('2026-05-01 09:00'), $this->dt('2026-05-01 12:00')]];
        $result = $this->invoke('subtractInterval', [
            $ranges,
            $this->dt('2026-05-01 12:00'),
            $this->dt('2026-05-01 14:00'),
        ]);
        $this->assertCount(1, $result);
        $this->assertEquals('12:00', $result[0][1]->format('H:i'));
    }

    // ============================================================
    // alignToStep
    // ============================================================

    public function test_align_to_step_already_aligned(): void
    {
        $dt = $this->dt('2026-05-01 09:00');
        $result = $this->invoke('alignToStep', [$dt, 30]);
        $this->assertEquals('09:00', $result->format('H:i'));
    }

    public function test_align_to_step_rounds_up_to_next_multiple(): void
    {
        $dt = $this->dt('2026-05-01 09:05');
        $result = $this->invoke('alignToStep', [$dt, 30]);
        $this->assertEquals('09:30', $result->format('H:i'));
    }

    public function test_align_to_step_rounds_up_across_hour(): void
    {
        $dt = $this->dt('2026-05-01 09:45');
        $result = $this->invoke('alignToStep', [$dt, 30]);
        $this->assertEquals('10:00', $result->format('H:i'));
    }

    public function test_align_to_step_15_minute_step(): void
    {
        $dt = $this->dt('2026-05-01 09:07');
        $result = $this->invoke('alignToStep', [$dt, 15]);
        $this->assertEquals('09:15', $result->format('H:i'));
    }

    public function test_align_to_step_60_minute_step(): void
    {
        $dt = $this->dt('2026-05-01 09:31');
        $result = $this->invoke('alignToStep', [$dt, 60]);
        $this->assertEquals('10:00', $result->format('H:i'));
    }

    // ============================================================
    // buildWorkRanges
    // ============================================================

    public function test_build_work_ranges_without_break(): void
    {
        $date = $this->dt('2026-05-01 00:00');
        $schedule = ['start_time' => '09:00', 'end_time' => '18:00'];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        $this->assertCount(1, $ranges);
        $this->assertEquals('09:00', $ranges[0][0]->format('H:i'));
        $this->assertEquals('18:00', $ranges[0][1]->format('H:i'));
    }

    public function test_build_work_ranges_with_break_splits_day(): void
    {
        $date = $this->dt('2026-05-01 00:00');
        $schedule = [
            'start_time' => '09:00', 'end_time' => '18:00',
            'break_start' => '12:30', 'break_end' => '14:00',
        ];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        $this->assertCount(2, $ranges);
        $this->assertEquals('09:00', $ranges[0][0]->format('H:i'));
        $this->assertEquals('12:30', $ranges[0][1]->format('H:i'));
        $this->assertEquals('14:00', $ranges[1][0]->format('H:i'));
        $this->assertEquals('18:00', $ranges[1][1]->format('H:i'));
    }

    public function test_build_work_ranges_invalid_times_returns_empty(): void
    {
        $date = $this->dt('2026-05-01 00:00');
        // end antes de start
        $schedule = ['start_time' => '18:00', 'end_time' => '09:00'];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        $this->assertEmpty($ranges);
    }

    public function test_build_work_ranges_break_at_boundary_collapses_range(): void
    {
        // Break que empieza exactamente al abrir — debería eliminar primer tramo
        $date = $this->dt('2026-05-01 00:00');
        $schedule = [
            'start_time' => '09:00', 'end_time' => '18:00',
            'break_start' => '09:00', 'break_end' => '10:00',
        ];
        $ranges = $this->invoke('buildWorkRanges', [$date, $schedule]);
        $this->assertCount(1, $ranges);
        $this->assertEquals('10:00', $ranges[0][0]->format('H:i'));
        $this->assertEquals('18:00', $ranges[0][1]->format('H:i'));
    }
}
