<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Servicios</h2>
        <p class="text-slate-500 text-sm">Lo que ofrecés en tu negocio</p>
    </div>
    <a href="/dashboard/services/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">+ Nuevo servicio</a>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($services)): ?>
        <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400">Aún no hay servicios cargados.</div>
    <?php endif; ?>
    <?php foreach ($services as $s): ?>
        <a href="/dashboard/services/<?= e($s['id']) ?>/edit" class="bg-white rounded-2xl border border-slate-200 p-5 hover:border-indigo-400 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-2">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold" style="background-color: <?= e($s['color']) ?>">
                    <?= strtoupper(substr($s['name'], 0, 1)) ?>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $s['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                    <?= $s['is_active'] ? 'Activo' : 'Inactivo' ?>
                </span>
            </div>
            <div class="mt-3 font-semibold text-slate-800"><?= e($s['name']) ?></div>
            <div class="text-sm text-slate-500 mt-1"><?= e($s['description'] ?? '') ?></div>
            <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
                <span><?= (int) $s['duration'] ?> min</span>
                <?php if (!empty($s['price'])): ?><span>·</span><span><?= format_money($s['price'], $s['currency']) ?></span><?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>
<?php View::endSection(); ?>
