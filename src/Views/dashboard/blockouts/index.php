<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200 font-semibold">Bloqueos activos</div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($blockouts)): ?>
                <div class="p-8 text-center text-slate-400 text-sm">No hay bloqueos cargados</div>
            <?php else: foreach ($blockouts as $b): ?>
                <div class="px-5 py-4 flex items-center justify-between">
                    <div>
                        <div class="font-medium"><?= e($b['title'] ?? 'Bloqueo') ?></div>
                        <div class="text-xs text-slate-500"><?= format_date($b['start_date'], 'd/m/Y H:i') ?> → <?= format_date($b['end_date'], 'd/m/Y H:i') ?></div>
                    </div>
                    <form method="POST" action="/dashboard/blockouts/<?= e($b['id']) ?>/delete" onsubmit="return confirm('¿Eliminar?');">
                        <?= csrf_field() ?>
                        <button class="text-red-600 text-xs hover:underline">Eliminar</button>
                    </form>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold mb-4">Nuevo bloqueo</h3>
        <form method="POST" action="/dashboard/blockouts" class="space-y-3">
            <?= csrf_field() ?>
            <input type="text" name="title" placeholder="Ej: Vacaciones" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
            <select name="professional_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                <option value="">Todo el negocio</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div>
                <label class="text-xs text-slate-500">Desde</label>
                <input type="datetime-local" name="start_date" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <div>
                <label class="text-xs text-slate-500">Hasta</label>
                <input type="datetime-local" name="end_date" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
            </div>
            <button class="w-full py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 text-sm">Crear bloqueo</button>
        </form>
    </div>
</div>
<?php View::endSection(); ?>
