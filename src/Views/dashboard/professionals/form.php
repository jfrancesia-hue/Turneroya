<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl">
    <form method="POST" action="<?= e($action) ?>" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
            <input type="text" name="name" required value="<?= e($professional['name'] ?? '') ?>"
                   class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:border-indigo-500 outline-none">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="<?= e($professional['email'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                <input type="tel" name="phone" value="<?= e($professional['phone'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Especialización</label>
            <input type="text" name="specialization" value="<?= e($professional['specialization'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bio</label>
            <textarea name="bio" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-slate-300"><?= e($professional['bio'] ?? '') ?></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Color del calendario</label>
                <input type="color" name="color" value="<?= e($professional['color'] ?? '#3B82F6') ?>" class="w-full h-11 px-2 rounded-lg border border-slate-300">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" <?= empty($professional) || !empty($professional['is_active']) ? 'checked' : '' ?>>
                    <span class="text-sm">Profesional activo</span>
                </label>
            </div>
        </div>

        <?php if (!empty($services)): ?>
        <div class="pt-4 border-t border-slate-200">
            <label class="block text-sm font-medium text-slate-700 mb-2">Servicios que realiza</label>
            <div class="grid grid-cols-2 gap-2">
                <?php foreach ($services as $s): ?>
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50">
                        <input type="checkbox" name="service_ids[]" value="<?= e($s['id']) ?>"
                               <?= in_array($s['id'], $selectedServices, true) ? 'checked' : '' ?>>
                        <span class="text-sm"><?= e($s['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Guardar</button>
            <a href="/dashboard/professionals" class="text-slate-600 text-sm">Cancelar</a>
            <?php if ($professional): ?>
                <form method="POST" action="/dashboard/professionals/<?= e($professional['id']) ?>/delete" class="ml-auto" onsubmit="return confirm('¿Eliminar profesional?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-red-600 text-sm hover:underline">Eliminar</button>
                </form>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php View::endSection(); ?>
