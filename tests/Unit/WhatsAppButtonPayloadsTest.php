<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\WhatsAppButtonPayloads;

/**
 * Tests del helper de payloads de botones WhatsApp.
 * Cubre build, parse, round-trip y casos negativos.
 */
final class WhatsAppButtonPayloadsTest extends TestCase
{
    public function test_build_generates_canonical_format(): void
    {
        $this->assertSame(
            'tya:confirm:abc-123',
            WhatsAppButtonPayloads::build(WhatsAppButtonPayloads::ACTION_CONFIRM, 'abc-123')
        );
    }

    public function test_parse_returns_struct_for_confirm(): void
    {
        $parsed = WhatsAppButtonPayloads::parse('tya:confirm:abc-123');
        $this->assertSame(['action' => 'confirm', 'booking_id' => 'abc-123'], $parsed);
    }

    public function test_parse_returns_struct_for_cancel(): void
    {
        $parsed = WhatsAppButtonPayloads::parse('tya:cancel:xyz');
        $this->assertSame(['action' => 'cancel', 'booking_id' => 'xyz'], $parsed);
    }

    public function test_parse_returns_struct_for_reschedule(): void
    {
        $parsed = WhatsAppButtonPayloads::parse('tya:reschedule:9f8e7d');
        $this->assertSame(['action' => 'reschedule', 'booking_id' => '9f8e7d'], $parsed);
    }

    public function test_parse_returns_null_for_non_tya_prefix(): void
    {
        $this->assertNull(WhatsAppButtonPayloads::parse('foo:confirm:abc'));
        $this->assertNull(WhatsAppButtonPayloads::parse('confirm:abc'));
    }

    public function test_parse_returns_null_for_unknown_action(): void
    {
        $this->assertNull(WhatsAppButtonPayloads::parse('tya:delete:abc'));
        $this->assertNull(WhatsAppButtonPayloads::parse('tya:foo:bar'));
    }

    public function test_parse_returns_null_for_empty_booking_id(): void
    {
        $this->assertNull(WhatsAppButtonPayloads::parse('tya:confirm:'));
    }

    public function test_parse_returns_null_for_string_without_colon(): void
    {
        $this->assertNull(WhatsAppButtonPayloads::parse('tya:confirm'));
        $this->assertNull(WhatsAppButtonPayloads::parse('tya:'));
    }

    public function test_round_trip_produces_same_input(): void
    {
        $bookingId = 'booking-uuid-1234-5678';
        foreach ([WhatsAppButtonPayloads::ACTION_CONFIRM, WhatsAppButtonPayloads::ACTION_CANCEL, WhatsAppButtonPayloads::ACTION_RESCHEDULE] as $action) {
            $built = WhatsAppButtonPayloads::build($action, $bookingId);
            $parsed = WhatsAppButtonPayloads::parse($built);
            $this->assertNotNull($parsed);
            $this->assertSame($action, $parsed['action']);
            $this->assertSame($bookingId, $parsed['booking_id']);
        }
    }
}
