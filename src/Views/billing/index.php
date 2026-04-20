<?php use TurneroYa\Core\View; use TurneroYa\Core\Session; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-ink-900">Facturación</h1>
            <p class="mt-1 text-ink-600">Administrá tu plan, pagos y consumo</p>
        </div>
        <a href="/pricing" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Ver todos los planes →</a>
    </div>

    <?php if (!empty($status_message) && $status_message === 'ok'): ?>
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 text-sm">
            <strong>¡Listo!</strong> Tu suscripción se inició correctamente. Puede tardar unos minutos en reflejarse.
        </div>
    <?php endif; ?>

    <!-- Plan actual -->
    <div class="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-700 text-white p-8 shadow-brand mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-brand-200">Plan actual</div>
                <h2 class="mt-2 text-4xl font-extrabold"><?= e($plan['name'] ?? 'Free') ?></h2>
                <?php if (!empty($plan['tagline'])): ?>
                    <p class="mt-2 text-brand-100"><?= e($plan['tagline']) ?></p>
                <?php endif; ?>
            </div>
            <div class="text-right">
                <?php if (!empty($subscription)): ?>
                    <?php if ($subscription['status'] === 'TRIALING'): ?>
                        <span class="inline-block px-3 py-1.5 rounded-full bg-amber-400 text-ink-900 text-xs font-bold uppercase tracking-wider">
                            Prueba gratis
                        </span>
                        <div class="mt-2 text-sm text-brand-100">
                            Hasta <?= e((new DateTime($subscription['trial_ends_at']))->format('d/m/Y')) ?>
                        </div>
                    <?php elseif ($subscription['status'] === 'ACTIVE'): ?>
                        <span class="inline-block px-3 py-1.5 rounded-full bg-emerald-500 text-white text-xs font-bold uppercase tracking-wider">
                            Activo
                        </span>
                        <div class="mt-2 text-sm text-brand-100">
                            Próximo cobro: <?= e((new DateTime($subscription['current_period_end']))->format('d/m/Y')) ?>
                        </div>
                    <?php elseif ($subscription['status'] === 'PAST_DUE'): ?>
                        <span class="inline-block px-3 py-1.5 rounded-full bg-red-500 text-white text-xs font-bold uppercase tracking-wider">
                            Pago pendiente
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="inline-block px-3 py-1.5 rounded-full bg-white/20 text-white text-xs font-bold uppercase tracking-wider">
                        Sin suscripción
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($subscription) && $subscription['status'] !== 'CANCELLED'): ?>
            <form method="POST" action="/dashboard/billing/cancel" class="mt-6 pt-6 border-t border-white/20"
                  onsubmit="return confirm('¿Seguro que querés cancelar tu suscripción? Seguirás usando el servicio hasta el fin del período actual.')">
                <input type="hidden" name="_csrf" value="<?= e(Session::csrf()) ?>">
                <button type="submit" class="text-sm font-semibold text-white/80 hover:text-white underline">
                    Cancelar suscripción
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Consumo -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-xl bg-white border border-ink-200/70 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Turnos este mes</div>
            <div class="mt-2 text-3xl font-bold text-ink-900"><?= (int) ($usage['bookings_count'] ?? 0) ?></div>
            <?php if (!empty($plan['max_bookings_per_month'])): ?>
                <div class="mt-3 text-xs text-ink-500">
                    Límite: <?= (int) $plan['max_bookings_per_month'] ?>
                </div>
                <div class="mt-2 h-2 rounded-full bg-ink-100 overflow-hidden">
                    <?php $pct = min(100, (int) round(((int) $usage['bookings_count'] / (int) $plan['max_bookings_per_month']) * 100)); ?>
                    <div class="h-full bg-brand-600" style="width: <?= $pct ?>%"></div>
                </div>
            <?php else: ?>
                <div class="mt-3 text-xs text-emerald-600 font-semibold">✓ Ilimitado</div>
            <?php endif; ?>
        </div>

        <div class="rounded-xl bg-white border border-ink-200/70 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Mensajes del bot</div>
            <div class="mt-2 text-3xl font-bold text-ink-900"><?= (int) ($usage['bot_messages_count'] ?? 0) ?></div>
            <?php if (!empty($plan['has_whatsapp_bot'])): ?>
                <div class="mt-3 text-xs text-emerald-600 font-semibold">✓ Incluido</div>
            <?php else: ?>
                <div class="mt-3 text-xs text-ink-500">Upgrade para habilitar</div>
            <?php endif; ?>
        </div>

        <div class="rounded-xl bg-white border border-ink-200/70 p-5">
            <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Recordatorios enviados</div>
            <div class="mt-2 text-3xl font-bold text-ink-900"><?= (int) ($usage['reminders_sent_count'] ?? 0) ?></div>
            <div class="mt-3 text-xs text-emerald-600 font-semibold">✓ Ilimitado</div>
        </div>
    </div>

    <!-- Cambiar de plan -->
    <?php if (empty($subscription) || in_array($subscription['status'] ?? null, ['CANCELLED','EXPIRED'], true) || ($plan['id'] ?? 'FREE') === 'FREE'): ?>
        <div class="rounded-2xl bg-white border border-ink-200/70 p-8 mb-8">
            <h2 class="text-xl font-bold text-ink-900 mb-2">Upgradeá tu plan</h2>
            <p class="text-ink-600 mb-6">Más turnos, más profesionales, bot de WhatsApp y analytics avanzado.</p>

            <form method="POST" action="/dashboard/billing/subscribe" class="space-y-4">
                <input type="hidden" name="_csrf" value="<?= e(Session::csrf()) ?>">

                <div>
                    <label class="block text-sm font-semibold text-ink-700 mb-2">Elegí tu plan</label>
                    <div class="grid md:grid-cols-3 gap-3">
                        <?php foreach ($all_plans as $p):
                            if ($p['id'] === 'FREE') continue; ?>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="plan_id" value="<?= e($p['id']) ?>"
                                       <?= $p['is_featured'] ? 'checked' : '' ?>
                                       class="peer sr-only">
                                <div class="rounded-xl border-2 border-ink-200 p-4 peer-checked:border-brand-600 peer-checked:bg-brand-50 transition">
                                    <div class="font-bold text-ink-900"><?= e($p['name']) ?></div>
                                    <div class="mt-1 text-2xl font-extrabold text-ink-900">
                                        AR$ <?= number_format((float) $p['price_monthly'], 0, ',', '.') ?>
                                        <span class="text-sm text-ink-500 font-normal">/mes</span>
                                    </div>
                                    <div class="mt-2 text-xs text-ink-500"><?= e($p['tagline']) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink-700 mb-2">Ciclo de facturación</label>
                        <select name="billing_cycle" class="w-full px-4 py-3 rounded-xl border border-ink-200 focus-ring">
                            <option value="MONTHLY">Mensual</option>
                            <option value="YEARLY">Anual (2 meses gratis)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink-700 mb-2">Email de facturación</label>
                        <input type="email" name="billing_email" required
                               class="w-full px-4 py-3 rounded-xl border border-ink-200 focus-ring"
                               placeholder="facturacion@tunegocio.com">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between">
                    <div class="text-xs text-ink-500">
                        Pagás con MercadoPago. Cancelás cuando quieras.
                    </div>
                    <button type="submit" class="btn-press px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-semibold shadow-brand">
                        Continuar a pago →
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Historial de facturas -->
    <?php if (!empty($invoices)): ?>
        <div class="rounded-2xl bg-white border border-ink-200/70 overflow-hidden">
            <div class="p-6 border-b border-ink-100">
                <h2 class="text-xl font-bold text-ink-900">Historial de pagos</h2>
            </div>
            <table class="w-full">
                <thead class="bg-ink-50">
                    <tr class="text-left text-xs font-bold uppercase tracking-wider text-ink-500">
                        <th class="px-6 py-3">Fecha</th>
                        <th class="px-6 py-3">Período</th>
                        <th class="px-6 py-3">Monto</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    <?php foreach ($invoices as $inv): ?>
                        <tr class="text-sm">
                            <td class="px-6 py-4"><?= e((new DateTime($inv['created_at']))->format('d/m/Y')) ?></td>
                            <td class="px-6 py-4 text-ink-600">
                                <?= e((new DateTime($inv['period_start']))->format('d/m')) ?> → <?= e((new DateTime($inv['period_end']))->format('d/m/Y')) ?>
                            </td>
                            <td class="px-6 py-4 font-semibold">AR$ <?= number_format((float) $inv['amount'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $statusClasses = [
                                    'PAID' => 'bg-emerald-100 text-emerald-700',
                                    'PENDING' => 'bg-amber-100 text-amber-700',
                                    'FAILED' => 'bg-red-100 text-red-700',
                                    'REFUNDED' => 'bg-ink-100 text-ink-700',
                                    'CANCELLED' => 'bg-ink-100 text-ink-700',
                                ];
                                $cls = $statusClasses[$inv['status']] ?? 'bg-ink-100 text-ink-700';
                                ?>
                                <span class="inline-block px-2 py-1 rounded-md <?= $cls ?> text-xs font-semibold">
                                    <?= e($inv['status']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if (!empty($inv['afip_invoice_url'])): ?>
                                    <a href="<?= e($inv['afip_invoice_url']) ?>" class="text-brand-600 hover:text-brand-700 text-sm font-semibold">
                                        Ver factura
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php View::endSection(); ?>
