<?php
declare(strict_types=1);

namespace TurneroYa\Models;

use TurneroYa\Core\Database;

final class Subscription
{
    public const TRIAL_DAYS = 14;

    public static function find(string $id): ?array
    {
        return Database::fetchOne('SELECT * FROM subscriptions WHERE id = :id', ['id' => $id]);
    }

    public static function findByPreapprovalId(string $preapprovalId): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM subscriptions WHERE mp_preapproval_id = :pid',
            ['pid' => $preapprovalId]
        );
    }

    /**
     * Suscripción activa (TRIALING, ACTIVE, PAST_DUE, PAUSED) del negocio.
     */
    public static function activeForBusiness(string $businessId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM subscriptions
             WHERE business_id = :b
               AND status IN ('TRIALING','ACTIVE','PAST_DUE','PAUSED')
             ORDER BY created_at DESC
             LIMIT 1",
            ['b' => $businessId]
        );
    }

    /**
     * Plan activo (con detalles del plan) para un negocio.
     * Si no tiene suscripción, devuelve el plan FREE.
     */
    public static function currentPlanFor(string $businessId): array
    {
        $row = Database::fetchOne(
            "SELECT p.*, s.status as sub_status, s.current_period_end, s.trial_ends_at
             FROM subscriptions s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.business_id = :b
               AND s.status IN ('TRIALING','ACTIVE','PAST_DUE','PAUSED')
             ORDER BY s.created_at DESC
             LIMIT 1",
            ['b' => $businessId]
        );

        if ($row) {
            return self::hydratePlan($row);
        }

        // Sin suscripción → plan FREE
        $free = Database::fetchOne('SELECT * FROM plans WHERE id = :id', ['id' => 'FREE']);
        return $free ? self::hydratePlan($free) : [];
    }

    public static function createTrial(string $businessId, string $planId, string $billingCycle = 'MONTHLY'): string
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            throw new \RuntimeException("Plan $planId no existe");
        }

        $trialEnd = (new \DateTimeImmutable())
            ->add(new \DateInterval('P' . self::TRIAL_DAYS . 'D'));

        $amount = $billingCycle === 'YEARLY'
            ? ($plan['price_yearly'] ?? $plan['price_monthly'] * 12)
            : $plan['price_monthly'];

        return Database::insert('subscriptions', [
            'business_id' => $businessId,
            'plan_id' => $planId,
            'status' => 'TRIALING',
            'billing_cycle' => $billingCycle,
            'trial_ends_at' => $trialEnd->format('Y-m-d H:i:sP'),
            'current_period_start' => (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
            'current_period_end' => $trialEnd->format('Y-m-d H:i:sP'),
            'amount' => $amount,
            'currency' => $plan['currency'] ?? 'ARS',
        ]);
    }

    public static function update(string $id, array $data): int
    {
        return Database::update('subscriptions', $id, $data);
    }

    public static function cancel(string $id, bool $immediate = false): void
    {
        $data = [
            'cancel_at_period_end' => true,
            'cancelled_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:sP'),
        ];
        if ($immediate) {
            $data['status'] = 'CANCELLED';
        }
        self::update($id, $data);
    }

    /**
     * Convierte el join plan+subscription en estructura usable.
     */
    private static function hydratePlan(array $row): array
    {
        if (isset($row['features_json']) && is_string($row['features_json'])) {
            $decoded = json_decode($row['features_json'], true);
            $row['features'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['features'] = [];
        }

        foreach ([
            'has_whatsapp_bot','has_advanced_analytics','has_public_booking','has_reminders',
            'has_deposits','has_custom_branding','has_api_access','has_multi_location',
            'has_priority_support','is_featured','is_active',
        ] as $k) {
            if (array_key_exists($k, $row)) {
                $row[$k] = (bool) $row[$k];
            }
        }
        return $row;
    }
}
