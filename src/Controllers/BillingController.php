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
        try {
            $plans = Plan::allActive();
        } catch (\Throwable $e) {
            error_log('[Billing pricing] fallback plans: ' . $e->getMessage());
            $plans = $this->fallbackPlans();
        }

        return view('billing/pricing', [
            'title' => 'Planes y precios',
            'plans' => $plans,
            'current_plan' => Auth::businessId() ? Subscription::currentPlanFor(Auth::businessId()) : null,
        ]);
    }

    private function fallbackPlans(): array
    {
        return [
            [
                'id' => 'FREE',
                'name' => 'Free',
                'tagline' => 'Para probar la plataforma sin costo.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'is_featured' => false,
                'features' => ['Hasta 50 turnos/mes', 'Pagina publica de reservas', '1 profesional', 'Soporte por email'],
            ],
            [
                'id' => 'STARTER',
                'name' => 'Starter',
                'tagline' => 'Para profesionales independientes.',
                'price_monthly' => 9900,
                'price_yearly' => 99000,
                'is_featured' => false,
                'features' => ['Hasta 500 turnos/mes', '2 profesionales', 'Recordatorios WhatsApp', 'Pagos con MercadoPago'],
            ],
            [
                'id' => 'NEGOCIO',
                'name' => 'Negocio',
                'tagline' => 'Con bot IA de WhatsApp para vender mas turnos.',
                'price_monthly' => 24900,
                'price_yearly' => 249000,
                'is_featured' => true,
                'features' => ['Turnos ilimitados', 'Bot WhatsApp con IA', 'Recordatorios automaticos', 'Analytics completo', 'Soporte prioritario'],
            ],
            [
                'id' => 'MULTI_SUCURSAL',
                'name' => 'Multi-Sucursal',
                'tagline' => 'Para equipos, cadenas y franquicias.',
                'price_monthly' => 59900,
                'price_yearly' => 599000,
                'is_featured' => false,
                'features' => ['Todo lo de Negocio', 'Sucursales multiples', 'Profesionales ilimitados', 'Integraciones custom'],
            ],
        ];
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
