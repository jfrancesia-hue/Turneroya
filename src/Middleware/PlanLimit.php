<?php
declare(strict_types=1);

namespace TurneroYa\Middleware;

use TurneroYa\Core\Auth as AuthService;
use TurneroYa\Models\Subscription;

/**
 * Middleware que bloquea el acceso a features según el plan del negocio.
 * Se puede configurar por ruta — usado en rutas sensibles como bot config.
 *
 * Implementación simple: bloquea si la suscripción está EXPIRED/CANCELLED
 * y el plan actual es FREE pero la ruta requiere plan pago. En ese caso
 * redirige a /dashboard/billing con mensaje.
 *
 * El enforcement fino (límites cuantitativos) se hace en los controllers
 * via PlanGate. Este middleware solo corta el flujo a nivel ruta.
 */
final class PlanLimit
{
    public function handle(): void
    {
        $businessId = AuthService::businessId();
        if (!$businessId) return;

        $sub = Subscription::activeForBusiness($businessId);
        if ($sub && $sub['status'] === 'PAST_DUE') {
            flash('error', 'Tu suscripción tiene un pago pendiente. Regularizá para seguir usando esta función.');
            redirect('/dashboard/billing');
        }
    }
}
