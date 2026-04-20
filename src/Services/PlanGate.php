<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Core\Database;
use TurneroYa\Models\Subscription;
use TurneroYa\Models\UsageCounter;

/**
 * Policy object que responde: "¿este negocio puede hacer X?" según su plan.
 * Única fuente de verdad para enforcement de límites.
 *
 * Se usa desde:
 *  - Middleware PlanLimit (bloqueo antes de ejecutar controller)
 *  - Controllers/Views (ej: mostrar/ocultar botón "Crear profesional")
 *  - BotEngine (bloquear bot si el plan no lo incluye)
 */
final class PlanGate
{
    private array $plan;
    private string $businessId;

    public function __construct(string $businessId)
    {
        $this->businessId = $businessId;
        $this->plan = Subscription::currentPlanFor($businessId);
    }

    public function plan(): array
    {
        return $this->plan;
    }

    public function planId(): string
    {
        return (string) ($this->plan['id'] ?? 'FREE');
    }

    public function isTrialing(): bool
    {
        return ($this->plan['sub_status'] ?? null) === 'TRIALING';
    }

    public function isPastDue(): bool
    {
        return ($this->plan['sub_status'] ?? null) === 'PAST_DUE';
    }

    // ------------------------------------------------------------
    // Límites cuantitativos
    // ------------------------------------------------------------

    public function canCreateBooking(): bool
    {
        $max = $this->plan['max_bookings_per_month'] ?? null;
        if ($max === null) return true; // ilimitado

        $usage = UsageCounter::currentFor($this->businessId);
        return ((int) $usage['bookings_count']) < (int) $max;
    }

    public function canCreateProfessional(): bool
    {
        $max = $this->plan['max_professionals'] ?? null;
        if ($max === null) return true;
        $current = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM professionals WHERE business_id = :b AND is_active = TRUE',
            ['b' => $this->businessId]
        );
        return $current < (int) $max;
    }

    public function canCreateService(): bool
    {
        $max = $this->plan['max_services'] ?? null;
        if ($max === null) return true;
        $current = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM services WHERE business_id = :b AND is_active = TRUE',
            ['b' => $this->businessId]
        );
        return $current < (int) $max;
    }

    // ------------------------------------------------------------
    // Features (boolean)
    // ------------------------------------------------------------

    public function hasWhatsappBot(): bool
    {
        return (bool) ($this->plan['has_whatsapp_bot'] ?? false);
    }

    public function hasAdvancedAnalytics(): bool
    {
        return (bool) ($this->plan['has_advanced_analytics'] ?? false);
    }

    public function hasDeposits(): bool
    {
        return (bool) ($this->plan['has_deposits'] ?? false);
    }

    public function hasCustomBranding(): bool
    {
        return (bool) ($this->plan['has_custom_branding'] ?? false);
    }

    public function hasApiAccess(): bool
    {
        return (bool) ($this->plan['has_api_access'] ?? false);
    }

    // ------------------------------------------------------------
    // Helpers de presentación
    // ------------------------------------------------------------

    public function bookingsRemaining(): ?int
    {
        $max = $this->plan['max_bookings_per_month'] ?? null;
        if ($max === null) return null;
        $usage = UsageCounter::currentFor($this->businessId);
        return max(0, (int) $max - (int) $usage['bookings_count']);
    }

    public function bookingsUsagePercent(): int
    {
        $max = $this->plan['max_bookings_per_month'] ?? null;
        if ($max === null || (int) $max === 0) return 0;
        $usage = UsageCounter::currentFor($this->businessId);
        return min(100, (int) round(((int) $usage['bookings_count'] / (int) $max) * 100));
    }
}
