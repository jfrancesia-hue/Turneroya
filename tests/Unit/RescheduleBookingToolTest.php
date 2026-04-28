<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TurneroYa\Services\BotEngine;

final class RescheduleBookingToolTest extends TestCase
{
    private BotEngine $bot;

    protected function setUp(): void
    {
        $this->bot = new BotEngine('test-business-id');
    }

    public function test_reschedule_booking_tool_is_registered(): void
    {
        $tools = (new ReflectionMethod($this->bot, 'buildTools'))->invoke($this->bot);
        $names = array_column($tools, 'name');
        $this->assertContains('reschedule_booking', $names);

        $tool = null;
        foreach ($tools as $t) if ($t['name'] === 'reschedule_booking') { $tool = $t; break; }
        $this->assertNotNull($tool);
        $this->assertEquals(
            ['booking_id', 'new_date', 'new_start_time'],
            $tool['input_schema']['required']
        );
    }

    public function test_executeTool_returns_error_when_data_missing(): void
    {
        $result = (new ReflectionMethod($this->bot, 'executeTool'))->invoke(
            $this->bot,
            'reschedule_booking',
            [],
            '+5491111111111'
        );
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Faltan datos', $result['error']);
    }
}
