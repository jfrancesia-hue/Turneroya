<?php
declare(strict_types=1);

namespace TurneroYa\Controllers;

use TurneroYa\Core\Auth;
use TurneroYa\Core\Database;
use TurneroYa\Core\Request;
use TurneroYa\Core\Session;
use TurneroYa\Models\Business;
use TurneroYa\Models\Plan;
use TurneroYa\Models\Subscription;
use TurneroYa\Models\UsageCounter;
use TurneroYa\Services\SubscriptionService;

/**
 * Gestión de billing para el negocio logueado:
 *  - Pricing público
 *  - Vista de plan actual + uso
 *  - Iniciar suscripción (redirige a MercadoPago)
 *  - Cancelar suscripción
 *  - Historial de facturas
 */
final class BillingController
{
    /**
     * Página pública de pricing — fuera del grupo /dashboard.
     */
    public function pricing(): string
    {
        $plans = Plan::allActive();
        return view('billing/pricing', [
            'title' => 'Planes y precios',
            'plans' => $plans,
            'current_plan' => Auth::businessId() ? Subscription::currentPlanFor(Auth::businessId()) : null,
        ]);
    }

    /**
     * /dashboard/billing — estado del plan actual del negocio logueado.
     */
    public function index(): string
    {
        $businessId = Auth::businessId();
        if (!$businessId) redirect('/dashboard/onboarding');

        $plan = Subscription::currentPlanFor($businessId);
        $subscription = Subscription::activeForBusiness($businessId);
        $usage = UsageCounter::currentFor($businessId);
        $invoices = Database::fetchAll(
            'SELECT * FROM subscription_invoices
             WHERE business_id = :b
             ORDER BY created_at DESC
             LIMIT 20',
            ['b' => $businessId]
        );
        $allPlans = Plan::allActive();

        return view('billing/index', [
            'title' => 'Facturación',
            'plan' => $plan,
            'subscription' => $subscription,
            'usage' => $usage,
            'invoices' => $invoices,
            'all_plans' => $allPlans,
            'status_message' => Request::input('status'),
        ]);
    }

    /**
     * POST /dashboard/billing/subscribe — inicia flujo de suscripción.
     * Redirige al init_point de MercadoPago.
     */
    public function subscribe(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/billing');
        }

        $businessId = Auth::businessId();
        if (!$businessId) redirect('/dashboard/onboarding');

        $planId = (string) Request::input('plan_id', '');
        $billingCycle = (string) Request::input('billing_cycle', 'MONTHLY');
        $email = trim((string) Request::input('billing_email', ''));

        if (!in_array($billingCycle, ['MONTHLY', 'YEARLY'], true)) {
            flash('error', 'Ciclo de facturación inválido');
            redirect('/dashboard/billing');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email de facturación inválido');
            redirect('/dashboard/billing');
        }

        try {
            $service = new SubscriptionService();
            $result = $service->startSubscription($businessId, $planId, $billingCycle, $email);
            header('Location: ' . $result['init_point']);
            exit;
        } catch (\Throwable $e) {
            error_log('[Billing subscribe] ' . $e->getMessage());
            flash('error', 'No se pudo iniciar la suscripción: ' . $e->getMessage());
            redirect('/dashboard/billing');
        }
    }

    public function cancel(): void
    {
        if (!Session::verifyCsrf(Request::input('_csrf'))) {
            flash('error', 'Token inválido');
            redirect('/dashboard/billing');
        }

        $businessId = Auth::businessId();
        if (!$businessId) redirect('/dashboard');

        $sub = Subscription::activeForBusiness($businessId);
        if (!$sub) {
            flash('error', 'No hay suscripción activa para cancelar');
            redirect('/dashboard/billing');
        }

        try {
            (new SubscriptionService())->cancelSubscription($sub['id'], immediate: false);
            flash('success', 'La suscripción se cancelará al finalizar el período actual.');
        } catch (\Throwable $e) {
            error_log('[Billing cancel] ' . $e->getMessage());
            flash('error', 'No se pudo cancelar: ' . $e->getMessage());
        }
        redirect('/dashboard/billing');
    }
}
