<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl">
    <form method="POST" action="<?= e($action) ?>" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
            <input type="text" name="name" required value="<?= e($client['name'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                <input type="tel" name="phone" value="<?= e($client['phone'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp</label>
                <input type="tel" name="whatsapp_number" value="<?= e($client['whatsapp_number'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="<?= e($client['email'] ?? '') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-slate-300"><?= e($client['notes'] ?? '') ?></textarea>
        </div>
        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium">Guardar</button>
            <a href="/dashboard/clients" class="text-slate-600 text-sm">Cancelar</a>
        </div>
    </form>
</div>
<?php View::endSection(); ?>
