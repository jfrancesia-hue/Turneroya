<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
        <h3 class="font-semibold mb-4">Datos</h3>
        <form method="POST" action="/dashboard/clients/<?= e($client['id']) ?>" class="space-y-3">
            <?= csrf_field() ?>
            <input type="text" name="name" required value="<?= e($client['name']) ?>" class="w-full px-3 py-2 rounded border border-slate-300 text-sm">
            <input type="tel" name="phone" value="<?= e($client['phone'] ?? '') ?>" placeholder="Teléfono" class="w-full px-3 py-2 rounded border border-slate-300 text-sm">
            <input type="tel" name="whatsapp_number" value="<?= e($client['whatsapp_number'] ?? '') ?>" placeholder="WhatsApp" class="w-full px-3 py-2 rounded border border-slate-300 text-sm">
            <input type="email" name="email" value="<?= e($client['email'] ?? '') ?>" placeholder="Email" class="w-full px-3 py-2 rounded border border-slate-300 text-sm">
            <textarea name="notes" rows="3" placeholder="Notas" class="w-full px-3 py-2 rounded border border-slate-300 text-sm"><?= e($client['notes'] ?? '') ?></textarea>
            <button class="w-full py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">Guardar cambios</button>
        </form>
        <form method="POST" action="/dashboard/clients/<?= e($client['id']) ?>/delete" class="mt-4 pt-4 border-t border-slate-200" onsubmit="return confirm('¿Eliminar cliente?');">
            <?= csrf_field() ?>
            <button class="text-red-600 text-xs hover:underline">Eliminar cliente</button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200 font-semibold flex items-center justify-between">
            <span>Historial</span>
            <span class="text-xs text-slate-500"><?= count($history) ?> turnos</span>
        </div>
        <div class="divide-y divide-slate-100 max-h-[600px] overflow-auto">
            <?php if (empty($history)): ?>
                <div class="p-8 text-center text-slate-400 text-sm">Sin historial</div>
            <?php else: foreach ($history as $h): ?>
                <div class="px-5 py-3 flex items-center justify-between text-sm">
                    <div>
                        <div class="font-medium"><?= e($h['service_name']) ?></div>
                        <div class="text-xs text-slate-500"><?= e($h['professional_name'] ?? '—') ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs"><?= format_date($h['date'], 'd/m/Y') ?> · <?= e(substr($h['start_time'], 0, 5)) ?></div>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100"><?= e($h['status']) ?></span>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
