<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\WaitlistMatcher;

/**
 * Tests de la lógica pura de matching de waitlist.
 * No tocan DB — usan WaitlistMatcher::matches() directamente.
 */
final class WaitlistMatchTest extends TestCase
{
    /** Entry "base" amplia: matchea cualquier slot del servicio entre 2026-05-01 y 2026-05-31. */
    private function baseEntry(array $overrides = []): array
    {
        return array_merge([
            'service_id' => 'svc-1',
            'professional_id' => null,
            'preferred_date_from' => '2026-05-01',
            'preferred_date_to' => '2026-05-31',
            'preferred_time_from' => null,
            'preferred_time_to' => null,
        ], $overrides);
    }

    public function test_distinct_service_does_not_match(): void
    {
        $entry = $this->baseEntry();
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-OTHER', null, '2026-05-10', '10:00'));
    }

    public function test_specific_professional_must_match(): void
    {
        $entry = $this->baseEntry(['professional_id' => 'pro-A']);
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', 'pro-B', '2026-05-10', '10:00'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', 'pro-A', '2026-05-10', '10:00'));
    }

    public function test_null_professional_in_entry_matches_any(): void
    {
        $entry = $this->baseEntry(['professional_id' => null]);
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', 'pro-A', '2026-05-10', '10:00'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', 'pro-Z', '2026-05-10', '10:00'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '10:00'));
    }

    public function test_date_before_range_does_not_match(): void
    {
        $entry = $this->baseEntry();
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-04-30', '10:00'));
    }

    public function test_date_after_range_does_not_match(): void
    {
        $entry = $this->baseEntry();
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-06-01', '10:00'));
    }

    public function test_open_ended_date_range_matches_future(): void
    {
        $entry = $this->baseEntry(['preferred_date_to' => null]);
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-09-15', '10:00'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2030-01-01', '10:00'));
        // Pero antes del from sigue sin matchear
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-04-01', '10:00'));
    }

    public function test_time_before_from_does_not_match(): void
    {
        $entry = $this->baseEntry(['preferred_time_from' => '09:00']);
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '08:30'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '09:00'));
    }

    public function test_time_after_to_does_not_match(): void
    {
        $entry = $this->baseEntry(['preferred_time_to' => '12:00']);
        $this->assertFalse(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '12:30'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '12:00'));
    }

    public function test_everything_within_range_matches(): void
    {
        $entry = $this->baseEntry([
            'professional_id' => 'pro-A',
            'preferred_time_from' => '09:00',
            'preferred_time_to' => '12:00',
        ]);
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', 'pro-A', '2026-05-15', '10:30'));
    }

    public function test_optional_null_fields_do_not_restrict(): void
    {
        // Sin profesional, sin time_from, sin time_to: cualquier hora entra
        $entry = $this->baseEntry();
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-10', '00:01'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', 'pro-X', '2026-05-10', '23:59'));
    }

    public function test_boundaries_are_inclusive(): void
    {
        $entry = $this->baseEntry([
            'preferred_time_from' => '09:00',
            'preferred_time_to' => '12:00',
        ]);
        // Borde exacto del rango de fechas
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-01', '09:00'));
        $this->assertTrue(WaitlistMatcher::matches($entry, 'svc-1', null, '2026-05-31', '12:00'));
    }
}
