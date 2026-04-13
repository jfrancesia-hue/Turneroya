<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-3xl">
    <form method="POST" action="/dashboard/settings" class="space-y-6">
        <?= csrf_field() ?>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-4">Datos del negocio</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-slate-500">Nombre</label>
                    <input type="text" name="name" value="<?= e($business['name']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-500">Teléfono</label>
                        <input type="tel" name="phone" value="<?= e($business['phone']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">WhatsApp</label>
                        <input type="tel" name="whatsapp" value="<?= e($business['whatsapp']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Email</label>
                    <input type="email" name="email" value="<?= e($business['email']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Dirección</label>
                    <input type="text" name="address" value="<?= e($business['address']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-slate-500">Ciudad</label>
                        <input type="text" name="city" value="<?= e($business['city']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Provincia</label>
                        <input type="text" name="province" value="<?= e($business['province']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Descripción</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= e($business['description']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-700 mb-4">Reglas de turnos</h3>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Duración de slot (min)</label>
                    <input type="number" name="slot_duration" value="<?= (int) $business['slot_duration'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Días máximos de anticipación</label>
                    <input type="number" name="max_advance_days" value="<?= (int) $business['max_advance_days'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Horas mínimas de anticipación</label>
                    <input type="number" name="min_advance_hours" value="<?= (int) $business['min_advance_hours'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Recordatorio (horas antes)</label>
                    <input type="number" name="reminder_hours_before" value="<?= (int) $business['reminder_hours_before'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-500">Cancelación (horas antes máx)</label>
                    <input type="number" name="cancellation_hours_limit" value="<?= (int) $business['cancellation_hours_limit'] ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
                </div>
            </div>
            <div class="mt-4 space-y-2 text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="auto_reminder" value="1" <?= $business['auto_reminder'] ? 'checked' : '' ?>>
                    Enviar recordatorios automáticos
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="require_confirmation" value="1" <?= $business['require_confirmation'] ? 'checked' : '' ?>>
                    Requerir confirmación manual antes de aceptar cada turno
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="allow_cancellation" value="1" <?= $business['allow_cancellation'] ? 'checked' : '' ?>>
                    Permitir que los clientes cancelen sus turnos
                </label>
            </div>
        </div>

        <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Guardar cambios</button>
    </form>
</div>
<?php View::endSection(); ?>
