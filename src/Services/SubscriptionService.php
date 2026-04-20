<?php
declare(strict_types=1);

namespace TurneroYa\Services;

use TurneroYa\Core\Database;
use TurneroYa\Models\Business;
use TurneroYa\Models\Plan;
use TurneroYa\Models\Subscription;

/**
 * Gestión de suscripciones + integración con MercadoPago Preapproval
 * (cobros recurrentes mensuales/anuales).
 *
 * Docs MP Preapproval: https://www.mercadopago.com.ar/developers/es/reference/subscriptions/_preapproval/post
 */
final class SubscriptionService
{
    private const MP_API_BASE = 'https://api.mercadopago.com';

    /**
     * Inicia el flujo de suscripción: crea trial local + preapproval en MP.
     * Devuelve la URL de pago (init_point) a la que redirigir al usuario.
     *
     * Durante el trial no se cobra. Al finalizar, MP cobra automáticamente.
     */
    public function startSubscription(
        string $businessId,
        string $planId,
        string $billingCycle,
        string $payerEmail
    ): array {
        $plan = Plan::find($planId);
        if (!$plan) {
            throw new \RuntimeException("Plan $planId no existe");
        }
        if ($plan['id'] === 'FREE') {
            throw new \RuntimeException('El plan FREE no requiere suscripción');
        }

        $business = Business::find($businessId);
        if (!$business) {
            throw new \RuntimeException('Negocio no encontrado');
        }

        // Si ya tiene una suscripción activa, no crear otra
        $existing = Subscription::activeForBusiness($businessId);
        if ($existing) {
            throw new \RuntimeException('Ya existe una suscripción activa para este negocio');
        }

        $amount = $billingCycle === 'YEARLY'
            ? (float) ($plan['price_yearly'] ?? $plan['price_monthly'] * 12)
            : (float) $plan['price_monthly'];

        return Database::transaction(function () use ($businessId, $planId, $billingCycle, $payerEmail, $plan, $amount, $business) {
            // 1) Crear suscripción en trial
            $subscriptionId = Subscription::createTrial($businessId, $planId, $billingCycle);

            // 2) Crear preapproval en MercadoPago
            $mpResponse = $this->createMercadoPagoPreapproval(
                subscriptionId: $subscriptionId,
                planName: $plan['name'],
                amount: $amount,
                currency: $plan['currency'] ?? 'ARS',
                billingCycle: $billingCycle,
                payerEmail: $payerEmail,
                businessName: $business['name']
            );

            // 3) Guardar IDs de MP en la suscripción
            Subscription::update($subscriptionId, [
                'mp_preapproval_id' => $mpResponse['id'],
                'mp_payer_email' => $payerEmail,
                'mp_init_point' => $mpResponse['init_point'],
            ]);

            // 4) Actualizar el campo plan denormalizado en businesses
            Business::update($businessId, [
                'plan' => $planId,
                'current_subscription_id' => $subscriptionId,
                'billing_email' => $payerEmail,
            ]);

            return [
                'subscription_id' => $subscriptionId,
                'init_point' => $mpResponse['init_point'],
                'preapproval_id' => $mpResponse['id'],
            ];
        });
    }

    /**
     * Procesa una notificación de webhook de MP (topic=preapproval o payment).
     * Se invoca desde WebhookController::mercadopago().
     */
    public function handleWebhook(string $topic, string $resourceId): void
    {
        $token = $this->mpAccessToken();
        if (!$token) return;

        if ($topic === 'preapproval' || $topic === 'subscription_preapproval') {
            $this->handlePreapprovalEvent($resourceId);
            return;
        }

        if ($topic === 'authorized_payment' || $topic === 'subscription_authorized_payment') {
            $this->handleAuthorizedPaymentEvent($resourceId);
            return;
        }

        if ($topic === 'payment') {
            // Ya lo maneja MercadoPagoService::markDepositPaid para señas.
            // Aquí podríamos diferenciar por external_reference.
            return;
        }
    }

    public function cancelSubscription(string $subscriptionId, bool $immediate = false): void
    {
        $sub = Subscription::find($subscriptionId);
        if (!$sub) throw new \RuntimeException('Suscripción no encontrada');

        // Cancelar en MP si hay preapproval
        if (!empty($sub['mp_preapproval_id'])) {
            $this->updateMercadoPagoPreapproval($sub['mp_preapproval_id'], ['status' => 'cancelled']);
        }

        Subscription::cancel($subscriptionId, $immediate);

        if ($immediate) {
            Business::update($sub['business_id'], ['plan' => 'FREE']);
        }
    }

    // ============================================================
    // MercadoPago Preapproval API (HTTP directo)
    // ============================================================

    private function createMercadoPagoPreapproval(
        string $subscriptionId,
        string $planName,
        float $amount,
        string $currency,
        string $billingCycle,
        string $payerEmail,
        string $businessName
    ): array {
        $token = $this->mpAccessToken();
        if (!$token) {
            throw new \RuntimeException('MERCADOPAGO_ACCESS_TOKEN no configurado');
        }

        $frequency = $billingCycle === 'YEARLY' ? 12 : 1;

        $body = [
            'reason' => 'TurneroYa - Plan ' . $planName . ' (' . $businessName . ')',
            'external_reference' => $subscriptionId,
            'payer_email' => $payerEmail,
            'auto_recurring' => [
                'frequency' => $frequency,
                'frequency_type' => 'months',
                'transaction_amount' => $amount,
                'currency_id' => $currency,
            ],
            'back_url' => url('/dashboard/billing?status=ok'),
            'status' => 'pending',
        ];

        $response = $this->httpPost(self::MP_API_BASE . '/preapproval', $body, $token);

        if (empty($response['id']) || empty($response['init_point'])) {
            throw new \RuntimeException('MP no devolvió preapproval válido: ' . json_encode($response));
        }
        return $response;
    }

    private function updateMercadoPagoPreapproval(string $preapprovalId, array $body): array
    {
        $token = $this->mpAccessToken();
        if (!$token) return [];

        return $this->httpPut(self::MP_API_BASE . '/preapproval/' . $preapprovalId, $body, $token);
    }

    private function getMercadoPagoPreapproval(string $preapprovalId): ?array
    {
        $token = $this->mpAccessToken();
        if (!$token) return null;

        return $this->httpGet(self::MP_API_BASE . '/preapproval/' . $preapprovalId, $token);
    }

    private function getMercadoPagoAuthorizedPayment(string $paymentId): ?array
    {
        $token = $this->mpAccessToken();
        if (!$token) return null;

        return $this->httpGet(self::MP_API_BASE . '/authorized_payments/' . $paymentId, $token);
    }

    private function handlePreapprovalEvent(string $preapprovalId): void
    {
        $preapproval = $this->getMercadoPagoPreapproval($preapprovalId);
        if (!$preapproval) return;

        $sub = Subscription::findByPreapprovalId($preapprovalId);
        if (!$sub) return;

        $mpStatus = (string) ($preapproval['status'] ?? '');
        $localStatus = match ($mpStatus) {
            'authorized' => 'ACTIVE',
            'paused' => 'PAUSED',
            'cancelled' => 'CANCELLED',
            default => $sub['status'],
        };

        $update = [
            'status' => $localStatus,
            'mp_payer_id' => $preapproval['payer_id'] ?? null,
            'mp_next_payment_date' => $preapproval['next_payment_date'] ?? null,
        ];
        Subscription::update($sub['id'], array_filter($update, fn($v) => $v !== null));

        if ($localStatus === 'CANCELLED') {
            Business::update($sub['business_id'], ['plan' => 'FREE']);
        }
    }

    private function handleAuthorizedPaymentEvent(string $paymentId): void
    {
        $payment = $this->getMercadoPagoAuthorizedPayment($paymentId);
        if (!$payment) return;

        $preapprovalId = $payment['preapproval_id'] ?? null;
        if (!$preapprovalId) return;

        $sub = Subscription::findByPreapprovalId($preapprovalId);
        if (!$sub) return;

        $status = (string) ($payment['status'] ?? 'pending');
        $amount = (float) ($payment['transaction_amount'] ?? $sub['amount']);

        // Crear invoice si es un pago nuevo
        $existing = Database::fetchOne(
            'SELECT id FROM subscription_invoices WHERE mp_payment_id = :pid',
            ['pid' => (string) $payment['id']]
        );
        if (!$existing) {
            Database::insert('subscription_invoices', [
                'subscription_id' => $sub['id'],
                'business_id' => $sub['business_id'],
                'amount' => $amount,
                'currency' => $sub['currency'],
                'status' => $status === 'approved' ? 'PAID' : 'PENDING',
                'period_start' => $sub['current_period_start'],
                'period_end' => $sub['current_period_end'],
                'paid_at' => $status === 'approved' ? date('c') : null,
                'mp_payment_id' => (string) $payment['id'],
                'mp_payment_status' => $status,
                'mp_payment_method' => $payment['payment_method_id'] ?? null,
            ]);
        }

        // Si el pago fue aprobado, renovar el ciclo
        if ($status === 'approved') {
            $start = new \DateTimeImmutable('now');
            $interval = $sub['billing_cycle'] === 'YEARLY' ? 'P1Y' : 'P1M';
            $end = $start->add(new \DateInterval($interval));

            Subscription::update($sub['id'], [
                'status' => 'ACTIVE',
                'current_period_start' => $start->format('Y-m-d H:i:sP'),
                'current_period_end' => $end->format('Y-m-d H:i:sP'),
                'mp_last_payment_id' => (string) $payment['id'],
            ]);
        } elseif ($status === 'rejected' || $status === 'cancelled') {
            Subscription::update($sub['id'], ['status' => 'PAST_DUE']);
        }
    }

    // ============================================================
    // HTTP helpers
    // ============================================================

    private function mpAccessToken(): string
    {
        return (string) config('services.mercadopago.access_token');
    }

    private function httpGet(string $url, string $token): ?array
    {
        return $this->httpRequest('GET', $url, null, $token);
    }

    private function httpPost(string $url, array $body, string $token): array
    {
        return $this->httpRequest('POST', $url, $body, $token) ?? [];
    }

    private function httpPut(string $url, array $body, string $token): array
    {
        return $this->httpRequest('PUT', $url, $body, $token) ?? [];
    }

    private function httpRequest(string $method, string $url, ?array $body, string $token): ?array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 15,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('MP HTTP error: ' . $err);
        }
        if ($status >= 400) {
            throw new \RuntimeException("MP HTTP $status: $response");
        }

        $decoded = json_decode((string) $response, true);
        return is_array($decoded) ? $decoded : null;
    }
}
