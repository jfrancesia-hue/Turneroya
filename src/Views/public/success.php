<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>
<div class="min-h-screen bg-ink-50/50">

<!-- Top strip -->
<div class="bg-white border-b border-ink-200/60">
    <div class="max-w-3xl mx-auto px-4 py-3">
        <?php View::partial('partials/brand_logo', ['size' => 'sm']); ?>
    </div>
</div>

<div class="max-w-2xl mx-auto px-4 py-10 lg:py-16">

    <!-- Success hero -->
    <div class="text-center mb-10">
        <div class="relative inline-block">
            <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-2xl animate-fade-in-up">
                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="absolute -top-2 -right-2 text-2xl animate-float">🎉</div>
            <div class="absolute -bottom-2 -left-2 text-2xl animate-float" style="animation-delay: 1s">✨</div>
        </div>
        <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-ink-900">¡Turno confirmado!</h1>
        <p class="mt-2 text-ink-500 max-w-sm mx-auto">Te esperamos. Te enviamos todos los detalles por WhatsApp.</p>
    </div>

    <!-- Booking card -->
    <div class="rounded-3xl bg-white border border-ink-200/60 shadow-lift overflow-hidden">
        <!-- Top ribbon -->
        <div class="bg-gradient-to-r from-brand-600 via-brand-700 to-accent-700 px-6 py-4 text-white flex items-center justify-between">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider opacity-80">Turno</div>
                <div class="text-2xl font-extrabold">#<?= (int) $booking['booking_number'] ?></div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider opacity-80">Estado</div>
                <div class="text-sm font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                    Confirmado
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="p-6 space-y-5">
            <!-- Date hero -->
            <div class="flex items-center gap-4 pb-5 border-b border-ink-100">
                <div class="w-16 text-center rounded-2xl bg-brand-50 border-2 border-brand-100 py-2">
                    <div class="text-[10px] font-bold uppercase text-brand-600"><?= (new \DateTimeImmutable($booking['date']))->format('M') ?></div>
                    <div class="text-2xl font-extrabold text-ink-900 leading-none"><?= (new \DateTimeImmutable($booking['date']))->format('d') ?></div>
                    <div class="text-[10px] text-ink-500"><?= (new \DateTimeImmutable($booking['date']))->format('Y') ?></div>
                </div>
                <div class="flex-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Día y hora</div>
                    <div class="text-lg font-bold text-ink-900 capitalize">
                        <?php
                        $days = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                        $dow = (int) (new \DateTimeImmutable($booking['date']))->format('w');
                        echo e($days[$dow]);
                        ?>
                    </div>
                    <div class="text-sm text-ink-600 font-semibold mt-0.5">
                        <?= e(substr($booking['start_time'], 0, 5)) ?> · <?= e(substr($booking['end_time'], 0, 5)) ?>hs
                    </div>
                </div>
            </div>

            <!-- Service -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-ink-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-ink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Servicio</div>
                    <div class="font-bold text-ink-900"><?= e($booking['service_name']) ?></div>
                </div>
            </div>

            <!-- Professional -->
            <?php if (!empty($booking['professional_name'])): ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-ink-50 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-ink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Con</div>
                        <div class="font-bold text-ink-900"><?= e($booking['professional_name']) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Location -->
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-ink-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-ink-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.7 16.7L13.4 21a2 2 0 01-2.8 0l-4.3-4.3a8 8 0 1111.4 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-ink-400">Lugar</div>
                    <div class="font-bold text-ink-900"><?= e($business['name']) ?></div>
                    <?php if (!empty($business['address'])): ?>
                        <div class="text-sm text-ink-500 mt-0.5"><?= e($business['address']) ?><?= $business['city'] ? ', ' . e($business['city']) : '' ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($booking['price'])): ?>
                <div class="flex items-center justify-between pt-5 border-t border-ink-100">
                    <div class="text-sm text-ink-500 font-semibold">Total del servicio</div>
                    <div class="text-2xl font-extrabold text-ink-900"><?= format_money($booking['price'], 'ARS') ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reminder banner -->
        <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-yellow-50 border-t border-amber-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.16V11a6 6 0 00-4-5.66V5a2 2 0 10-4 0v.34C7.67 6.16 6 8.39 6 11v3.16c0 .53-.2 1.05-.59 1.43L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
            </div>
            <div class="text-xs text-amber-900">
                Te enviaremos un recordatorio <span class="font-bold"><?= (int) $business['reminder_hours_before'] ?>hs antes</span> por WhatsApp.
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex flex-wrap gap-3 justify-center">
        <a href="/book/<?= e($business['slug']) ?>" class="btn-press inline-flex items-center gap-2 px-5 py-3 bg-white border border-ink-200 text-ink-900 rounded-xl text-sm font-bold hover:bg-ink-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Reservar otro turno
        </a>
        <button onclick="navigator.share && navigator.share({title:'Mi turno',text:'Tengo turno en <?= e($business['name']) ?>',url:location.href})" class="btn-press inline-flex items-center gap-2 px-5 py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-bold transition shadow-brand">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.7 13.3a3 3 0 010-2.6m0 2.6a3 3 0 105.3-2.6m-5.3 2.6l6.6 3.8m0 0a3 3 0 105.3 2.6 3 3 0 00-5.3-2.6zm0-10.9a3 3 0 105.3-2.6 3 3 0 00-5.3 2.6zm0 0l-6.6 3.8"/></svg>
            Compartir
        </button>
    </div>

    <div class="mt-10 text-center text-xs text-ink-400">
        Powered by <span class="font-bold text-brand-600">TurneroYa</span>
    </div>
</div>
</div>
<?php View::endSection(); ?>
