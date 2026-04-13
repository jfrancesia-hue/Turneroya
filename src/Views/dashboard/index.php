<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>

<!-- Welcome Hero -->
<div class="relative mb-8 rounded-3xl overflow-hidden bg-gradient-to-br from-ink-950 via-brand-900 to-accent-900 text-white p-8 lg:p-10 shadow-lift">
    <div class="absolute inset-0 bg-grid-dark opacity-30"></div>
    <div class="absolute -top-32 -right-32 w-96 h-96 bg-brand-500/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 w-64 h-64 bg-accent-500/20 rounded-full blur-3xl"></div>

    <div class="relative grid lg:grid-cols-[2fr_1fr] gap-6 items-center">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <?= date('l, d \d\e F') ?>
            </div>
            <h2 class="mt-4 text-3xl lg:text-4xl font-extrabold tracking-tight">
                ¡Hola, <?= e(auth()['name'] ?? 'admin') ?>! 👋
            </h2>
            <p class="mt-2 text-white/70">
                Acá está el resumen de <span class="font-semibold text-white"><?= e($business['name']) ?></span>
            </p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="/dashboard/bookings/create" class="btn-press inline-flex items-center gap-2 px-4 py-2.5 bg-white text-ink-900 rounded-xl text-sm font-bold hover:bg-brand-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Nuevo turno
                </a>
                <a href="/dashboard/calendar" class="btn-press inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur border border-white/20 text-white rounded-xl text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Ver calendario
                </a>
            </div>
        </div>

        <!-- Quick link card -->
        <div class="rounded-2xl bg-white/10 backdrop-blur border border-white/20 p-5">
            <div class="text-xs uppercase tracking-wider font-bold text-white/60 mb-2">Tu link público</div>
            <div class="text-sm font-mono bg-black/30 rounded-lg px-3 py-2 mb-3 truncate">
                <?= e(url('/book/' . $business['slug'])) ?>
            </div>
            <a href="<?= e(url('/book/' . $business['slug'])) ?>" target="_blank" class="block text-center py-2 bg-white text-brand-700 rounded-lg text-xs font-bold hover:bg-brand-50">
                Abrir página →
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<?php
$cards = [
    [
        'label' => 'Turnos hoy',
        'value' => (int) $stats['bookings_today'],
        'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'bg' => 'bg-brand-50',
        'fg' => 'text-brand-600',
        'trend' => '+12%',
    ],
    [
        'label' => 'Turnos esta semana',
        'value' => (int) $stats['bookings_week'],
        'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'bg' => 'bg-accent-50',
        'fg' => 'text-accent-600',
        'trend' => '+8%',
    ],
    [
        'label' => 'Clientes totales',
        'value' => (int) $stats['clients_total'],
        'icon' => 'M17 20h5v-2a3 3 0 00-5.4-1.9M17 20H7m10 0v-2c0-.7-.1-1.3-.4-1.9M7 20H2v-2a3 3 0 015.4-1.9M7 20v-2c0-.7.1-1.3.4-1.9m0 0a5 5 0 019.3 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'bg' => 'bg-pink-50',
        'fg' => 'text-pink-600',
        'trend' => '+23%',
    ],
    [
        'label' => 'Profesionales',
        'value' => (int) $stats['professionals_active'],
        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'bg' => 'bg-emerald-50',
        'fg' => 'text-emerald-600',
        'trend' => 'activos',
    ],
];
?>
<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ($cards as $c): ?>
        <div class="group rounded-2xl bg-white border border-ink-200/70 p-5 hover:border-brand-300 hover:shadow-elev transition">
            <div class="flex items-start justify-between">
                <div class="w-11 h-11 rounded-xl <?= $c['bg'] ?> flex items-center justify-center">
                    <svg class="w-5 h-5 <?= $c['fg'] ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= $c['icon'] ?>"/>
                    </svg>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded"><?= e($c['trend']) ?></span>
            </div>
            <div class="mt-4 text-3xl font-extrabold text-ink-900 tracking-tight"><?= e($c['value']) ?></div>
            <div class="mt-0.5 text-xs text-ink-500 font-medium"><?= e($c['label']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Main content grid -->
<div class="grid lg:grid-cols-[2fr_1fr] gap-6">
    <!-- Next appointments -->
    <div class="rounded-2xl bg-white border border-ink-200/70 overflow-hidden">
        <div class="px-6 py-5 border-b border-ink-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-ink-900">Próximos turnos</h3>
                <p class="text-xs text-ink-500">Los siguientes 7 días</p>
            </div>
            <a href="/dashboard/calendar" class="text-sm text-brand-600 hover:text-brand-700 font-semibold">Ver todos →</a>
        </div>
        <div class="divide-y divide-ink-100">
            <?php if (empty($upcoming)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-ink-50 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-ink-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="text-sm text-ink-500">Aún no hay turnos agendados</div>
                    <a href="/dashboard/bookings/create" class="mt-3 inline-block text-sm text-brand-600 hover:text-brand-700 font-semibold">Crear primer turno →</a>
                </div>
            <?php else: ?>
                <?php foreach ($upcoming as $b): ?>
                    <a href="/dashboard/bookings/<?= e($b['id']) ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-ink-50/50 transition">
                        <!-- Date badge -->
                        <div class="w-14 flex-shrink-0 text-center rounded-xl bg-ink-50 border border-ink-100 py-2">
                            <div class="text-[10px] font-bold uppercase text-ink-500"><?= format_date($b['date'], 'M') ?></div>
                            <div class="text-xl font-extrabold text-ink-900 leading-none"><?= format_date($b['date'], 'd') ?></div>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-ink-900 truncate"><?= e($b['client_name']) ?></div>
                            <div class="text-sm text-ink-500 truncate flex items-center gap-1.5 mt-0.5">
                                <span class="inline-block w-2 h-2 rounded-full" style="background-color: <?= e($b['service_color'] ?? '#4F46E5') ?>"></span>
                                <?= e($b['service_name']) ?>
                                <?php if (!empty($b['professional_name'])): ?>
                                    · <?= e($b['professional_name']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Time + Status -->
                        <div class="text-right flex-shrink-0">
                            <div class="font-bold text-ink-900"><?= e(substr($b['start_time'], 0, 5)) ?></div>
                            <?php
                            $statusMap = [
                                'CONFIRMED' => ['bg-emerald-50', 'text-emerald-700', 'Confirmado'],
                                'PENDING' => ['bg-amber-50', 'text-amber-700', 'Pendiente'],
                                'COMPLETED' => ['bg-brand-50', 'text-brand-700', 'Completado'],
                            ];
                            [$bgc, $fgc, $lbl] = $statusMap[$b['status']] ?? ['bg-ink-50', 'text-ink-600', $b['status']];
                            ?>
                            <span class="inline-block mt-0.5 text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded <?= $bgc ?> <?= $fgc ?>"><?= e($lbl) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick actions sidebar -->
    <div class="space-y-4">
        <!-- Quick create card -->
        <div class="rounded-2xl bg-white border border-ink-200/70 p-5">
            <h3 class="font-bold text-ink-900 mb-3">Acciones rápidas</h3>
            <div class="space-y-2">
                <?php
                $actions = [
                    ['/dashboard/bookings/create', 'Nuevo turno', 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                    ['/dashboard/clients/create', 'Nuevo cliente', 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
                    ['/dashboard/services/create', 'Nuevo servicio', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['/dashboard/blockouts', 'Bloquear horario', 'M18.4 18.4A9 9 0 005.6 5.6m12.8 12.8A9 9 0 015.6 5.6m12.8 12.8L5.6 5.6'],
                ];
                foreach ($actions as [$url, $label, $icon]): ?>
                    <a href="<?= e($url) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-ink-50 transition group">
                        <div class="w-9 h-9 rounded-lg bg-ink-50 group-hover:bg-brand-100 flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-ink-500 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-ink-700 group-hover:text-ink-900"><?= e($label) ?></span>
                        <svg class="ml-auto w-4 h-4 text-ink-300 group-hover:text-ink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bot status card -->
        <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 text-white p-5 shadow-elev">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <div>
                    <div class="text-xs uppercase font-bold tracking-wider opacity-80">Bot WhatsApp</div>
                    <div class="text-sm font-bold flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-200 animate-pulse"></span>
                        Activo
                    </div>
                </div>
            </div>
            <p class="text-xs text-white/80 mt-3">
                Tu asistente con IA respondiendo 24/7
            </p>
            <a href="/dashboard/bot/config" class="mt-3 block text-center py-2 bg-white/20 hover:bg-white/30 backdrop-blur rounded-lg text-xs font-bold transition">
                Configurar →
            </a>
        </div>
    </div>
</div>

<?php View::endSection(); ?>
