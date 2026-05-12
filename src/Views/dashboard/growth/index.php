<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<?php
$money = $growth['money'];
$crm = $growth['crm'];
$agenda = $growth['agenda'];
$noShow = $growth['no_show'];
$automations = $growth['automations'];
$locations = $growth['locations'];
?>

<div class="space-y-6">
    <section class="grid xl:grid-cols-4 sm:grid-cols-2 gap-4">
        <?php foreach ([
            ['Ingresos 30 días', format_money($money['last_30_revenue']), 'Turnos confirmados/completados'],
            ['Proyección 30 días', format_money($money['next_30_projected']), $money['projected_bookings'] . ' turnos próximos'],
            ['Pérdida por ausencias', format_money($money['lost_to_absence']), $money['no_shows'] . ' no-shows recientes'],
            ['Reservas monetizadas', (string) $money['paid_like_bookings'], 'Base para forecast'],
        ] as [$label, $value, $meta]): ?>
            <article class="bg-white border border-ink-200/70 rounded-lg p-5">
                <span class="text-xs font-bold uppercase text-ink-400"><?= e($label) ?></span>
                <strong class="block mt-2 text-2xl font-extrabold text-ink-900"><?= e($value) ?></strong>
                <small class="block mt-1 text-xs text-ink-500"><?= e($meta) ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="grid xl:grid-cols-3 gap-6">
        <article class="xl:col-span-2 bg-white border border-ink-200/70 rounded-lg overflow-hidden">
            <div class="px-5 py-4 border-b border-ink-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase text-brand-600">Agenda inteligente</span>
                    <h2 class="font-bold text-ink-900">Huecos con más chance de venderse</h2>
                </div>
                <a href="/dashboard/bookings/create" class="text-sm font-semibold text-brand-700">Crear turno</a>
            </div>
            <div class="divide-y divide-ink-100">
                <?php if (empty($agenda['fillable_slots'])): ?>
                    <div class="p-8 text-sm text-ink-500">No encontré huecos recomendados todavía. Cargá servicios, profesionales y horarios para activar sugerencias.</div>
                <?php else: foreach ($agenda['fillable_slots'] as $slot): ?>
                    <div class="p-4 flex items-center justify-between gap-4">
                        <div>
                            <strong class="block text-sm text-ink-900"><?= e($slot['service_name']) ?></strong>
                            <span class="text-xs text-ink-500"><?= e(format_date($slot['date'], 'd/m/Y')) ?> · <?= e(substr((string) $slot['start_time'], 0, 5)) ?> · <?= e($slot['professional_name']) ?></span>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-bold text-emerald-700"><?= e($slot['price'] ? format_money($slot['price']) : 'A cotizar') ?></span>
                            <small class="text-xs text-ink-400">ofrecer a inactivos</small>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </article>

        <article class="bg-ink-900 text-white rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-lime-300">Anti no-show</span>
            <h2 class="mt-1 text-xl font-extrabold">Riesgo de ausencias</h2>
            <div class="mt-5 space-y-3">
                <?php if (empty($noShow['upcoming_risk'])): ?>
                    <p class="text-sm text-white/70">Sin turnos próximos con riesgo visible.</p>
                <?php else: foreach (array_slice($noShow['upcoming_risk'], 0, 5) as $booking): ?>
                    <div class="rounded-lg bg-white/10 border border-white/10 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <strong class="text-sm"><?= e($booking['client_name']) ?></strong>
                            <span class="text-xs font-bold <?= (int) $booking['risk_score'] >= 60 ? 'text-red-300' : 'text-lime-300' ?>"><?= (int) $booking['risk_score'] ?>%</span>
                        </div>
                        <p class="mt-1 text-xs text-white/70"><?= e($booking['service_name']) ?> · <?= e(format_date($booking['date'], 'd/m')) ?> <?= e(substr($booking['start_time'], 0, 5)) ?></p>
                        <p class="mt-2 text-xs text-white/80"><?= e($booking['risk_action']) ?></p>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </article>
    </section>

    <section class="grid xl:grid-cols-3 gap-6">
        <article class="bg-white border border-ink-200/70 rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-brand-600">CRM</span>
            <h2 class="mt-1 font-bold text-ink-900">Segmentos accionables</h2>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <div class="rounded-lg bg-amber-50 p-3">
                    <strong class="block text-xl text-amber-700"><?= count($crm['inactive']) ?></strong>
                    <span class="text-xs text-amber-800">Inactivos</span>
                </div>
                <div class="rounded-lg bg-emerald-50 p-3">
                    <strong class="block text-xl text-emerald-700"><?= count($crm['vip']) ?></strong>
                    <span class="text-xs text-emerald-800">Frecuentes</span>
                </div>
                <div class="rounded-lg bg-sky-50 p-3">
                    <strong class="block text-xl text-sky-700"><?= count($crm['new_leads']) ?></strong>
                    <span class="text-xs text-sky-800">Nuevos</span>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <?php foreach (array_slice($crm['inactive'], 0, 5) as $client): ?>
                    <a href="/dashboard/clients/<?= e($client['id']) ?>" class="block rounded-lg border border-ink-100 p-3 hover:bg-ink-50">
                        <strong class="block text-sm text-ink-900"><?= e($client['name']) ?></strong>
                        <span class="text-xs text-ink-500">Último turno: <?= e($client['last_booking_date'] ? format_date($client['last_booking_date'], 'd/m/Y') : 'sin turnos') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="bg-white border border-ink-200/70 rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-brand-600">Campañas WhatsApp</span>
            <h2 class="mt-1 font-bold text-ink-900">Mensajes listos para vender</h2>
            <div class="mt-4 space-y-3">
                <?php foreach ($crm['campaigns'] as $campaign): ?>
                    <div class="rounded-lg border border-ink-100 p-3">
                        <div class="flex items-center justify-between">
                            <strong class="text-sm text-ink-900"><?= e($campaign['segment']) ?></strong>
                            <span class="text-xs font-bold text-brand-700"><?= (int) $campaign['count'] ?></span>
                        </div>
                        <p class="mt-1 text-xs text-ink-500"><?= e($campaign['goal']) ?></p>
                        <p class="mt-2 text-xs bg-ink-50 rounded p-2 text-ink-700"><?= e($campaign['message']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="bg-white border border-ink-200/70 rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-brand-600">Automatizaciones</span>
            <h2 class="mt-1 font-bold text-ink-900">Piloto automático</h2>
            <div class="mt-4 space-y-3">
                <?php foreach ($automations as $auto): ?>
                    <div class="rounded-lg border border-ink-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <strong class="text-sm text-ink-900"><?= e($auto['name']) ?></strong>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-lime-100 text-lime-800"><?= e($auto['status']) ?></span>
                        </div>
                        <p class="mt-1 text-xs text-ink-500"><?= e($auto['description']) ?></p>
                        <small class="mt-2 block text-xs font-semibold text-ink-700"><?= e($auto['impact']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="grid lg:grid-cols-2 gap-6">
        <article class="bg-white border border-ink-200/70 rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-brand-600">Servicios rentables</span>
            <h2 class="mt-1 font-bold text-ink-900">Dónde empujar ventas</h2>
            <div class="mt-4 space-y-3">
                <?php foreach ($money['top_services'] as $service): ?>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <strong><?= e($service['name']) ?></strong>
                            <span><?= e(format_money($service['revenue'])) ?></span>
                        </div>
                        <div class="mt-1 h-2 bg-ink-100 rounded-full overflow-hidden">
                            <div class="h-full bg-brand-600" style="width: <?= min(100, max(8, ((float) $service['revenue'] / max(1, (float) $money['last_30_revenue'])) * 100)) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="bg-white border border-ink-200/70 rounded-lg p-5">
            <span class="text-xs font-bold uppercase text-brand-600">Multi-sucursal</span>
            <h2 class="mt-1 font-bold text-ink-900">Preparación actual</h2>
            <div class="mt-4 flex items-center gap-4">
                <div class="w-20 h-20 rounded-full bg-ink-900 text-white flex items-center justify-center text-xl font-extrabold"><?= (int) $locations['readiness_score'] ?>%</div>
                <div>
                    <strong class="block text-sm text-ink-900"><?= e($locations['current_location']['name']) ?></strong>
                    <span class="block text-xs text-ink-500"><?= e($locations['current_location']['city'] ?: 'Ciudad pendiente') ?> · <?= e($locations['current_location']['address'] ?: 'Dirección pendiente') ?></span>
                    <p class="mt-2 text-xs text-ink-600"><?= e($locations['next_step']) ?></p>
                </div>
            </div>
        </article>
    </section>
</div>
<?php View::endSection(); ?>
