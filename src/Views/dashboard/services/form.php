<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl">
    <form method="POST" action="<?= e($action) ?>" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
            <input type="text" name="name" required value="<?= e($service['name'] ?? '') ?>"
                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
            <textarea name="description" rows="2" class="w-full px-4 py-2.5 rounded-lg border border-slate-300"><?= e($service['description'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Duración (min) *</label>
                <input type="number" name="duration" required min="5" value="<?= e($service['duration'] ?? 30) ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Precio (ARS)</label>
                <input type="number" step="0.01" name="price" value="<?= e($service['price'] ?? '') ?>"
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Color</label>
                <input type="color" name="color" value="<?= e($service['color'] ?? '#10B981') ?>" class="w-full h-11 px-2 rounded-lg border border-slate-300">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" <?= empty($service) || !empty($service['is_active']) ? 'checked' : '' ?>>
                    <span class="text-sm">Servicio activo</span>
                </label>
            </div>
        </div>
        <div class="pt-4 border-t border-slate-200">
            <label class="flex items-center gap-2 mb-2">
                <input type="checkbox" name="requires_deposit" value="1" <?= !empty($service['requires_deposit']) ? 'checked' : '' ?>>
                <span class="text-sm font-medium">Requiere seña/depósito</span>
            </label>
            <input type="number" step="0.01" name="deposit_amount" value="<?= e($service['deposit_amount'] ?? '') ?>"
                   placeholder="Monto de la seña" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Guardar</button>
            <a href="/dashboard/services" class="text-slate-600 text-sm">Cancelar</a>
            <?php if ($service): ?>
                <form method="POST" action="/dashboard/services/<?= e($service['id']) ?>/delete" class="ml-auto" onsubmit="return confirm('¿Eliminar servicio?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-red-600 text-sm hover:underline">Eliminar</button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php View::endSection(); ?>
