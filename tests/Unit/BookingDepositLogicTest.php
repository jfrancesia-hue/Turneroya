<?php
declare(strict_types=1);

namespace TurneroYa\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TurneroYa\Services\BookingService;

/**
 * Tests de la lógica pura "¿este booking requiere seña?".
 * No tocan DB — usan BookingService::shouldRequireDeposit() directamente.
 */
final class BookingDepositLogicTest extends TestCase
{
    /** Servicio "base" que SI requiere seña (precio configurado). */
    private function depositService(array $overrides = []): array
    {
        return array_merge([
            'requires_deposit' => true,
            'deposit_amount' => '500.00',
        ], $overrides);
    }

    public function test_service_with_deposit_flags_requires_deposit(): void
    {
        $service = $this->depositService();
        $this->assertTrue(BookingService::shouldRequireDeposit($service, []));
    }

    public function test_service_without_requires_deposit_does_not(): void
    {
        $service = $this->depositService(['requires_deposit' => false]);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));
    }

    public function test_service_without_amount_does_not_require_deposit(): void
    {
        $service = $this->depositService(['deposit_amount' => null]);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));

        $service = $this->depositService(['deposit_amount' => 0]);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));

        $service = $this->depositService(['deposit_amount' => '0']);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));
    }

    public function test_negative_or_empty_amount_does_not_require_deposit(): void
    {
        $service = $this->depositService(['deposit_amount' => '-100']);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));

        $service = $this->depositService(['deposit_amount' => '']);
        $this->assertFalse(BookingService::shouldRequireDeposit($service, []));
    }

    public function test_skip_deposit_payload_overrides_service_config(): void
    {
        $service = $this->depositService();
        // Aunque el service requiere seña, el caller puede saltearlo
        // (ej. dashboard manual donde el negocio no cobra por chat).
        $this->assertFalse(BookingService::shouldRequireDeposit($service, ['skip_deposit' => true]));
        $this->assertFalse(BookingService::shouldRequireDeposit($service, ['skip_deposit' => 1]));
        $this->assertFalse(BookingService::shouldRequireDeposit($service, ['skip_deposit' => '1']));
    }

    public function test_skip_deposit_false_keeps_deposit_required(): void
    {
        $service = $this->depositService();
        // skip_deposit=false (o falsy) NO debe saltear el cobro
        $this->assertTrue(BookingService::shouldRequireDeposit($service, ['skip_deposit' => false]));
        $this->assertTrue(BookingService::shouldRequireDeposit($service, ['skip_deposit' => 0]));
        $this->assertTrue(BookingService::shouldRequireDeposit($service, ['skip_deposit' => '']));
    }

    public function test_amount_as_float_string_works(): void
    {
        $service = $this->depositService(['deposit_amount' => '1500.50']);
        $this->assertTrue(BookingService::shouldRequireDeposit($service, []));
    }

    public function test_missing_keys_default_to_no_deposit(): void
    {
        // Servicio sin ninguna de las dos claves: no requiere seña
        $this->assertFalse(BookingService::shouldRequireDeposit([], []));
        $this->assertFalse(BookingService::shouldRequireDeposit(['name' => 'Corte'], []));
    }
}
