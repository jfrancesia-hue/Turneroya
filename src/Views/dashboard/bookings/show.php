<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <div class="text-xs uppercase text-slate-500">Turno #<?= (int) $booking['booking_number'] ?></div>
                <div class="text-lg font-semibold"><?= e($booking['client_name']) ?></div>
            </div>
            <?php
            $map = [
                'PENDING' => 'bg-yellow-100 text-yellow-700',
                'CONFIRMED' => 'bg-green-100 text-green-700',
                'COMPLETED' => 'bg-blue-100 text-blue-700',
                'CANCELLED' => 'bg-slate-100 text-slate-500',
                'NO_SHOW' => 'bg-red-100 text-red-700',
                'RESCHEDULED' => 'bg-purple-100 text-purple-700',
            ];
            ?>
            <span class="px-3 py-1 rounded-full text-xs font-medium <?= $map[$booking['status']] ?? '' ?>"><?= e($booking['status']) ?></span>
        </div>
        <div class="px-6 py-4 space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Servicio</span><span><?= e($booking['service_name']) ?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Profesional</span><span><?= e($booking['professional_name'] ?? '—') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Fecha</span><span><?= format_date($booking['date'], 'l d/m/Y') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Horario</span><span><?= e(substr($booking['start_time'], 0, 5)) ?> - <?= e(substr($booking['end_time'], 0, 5)) ?></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Teléfono</span><span><?= e($booking['client_phone'] ?? '—') ?></span></div>
            <?php if (!empty($booking['notes'])): ?>
                <div class="pt-3 border-t border-slate-100">
                    <div class="text-slate-500 text-xs uppercase mb-1">Notas</div>
                    <div><?= nl2br(e($booking['notes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            <div class="text-xs text-slate-500 uppercase mb-2">Cambiar estado</div>
            <div class="flex flex-wrap gap-2">
                <?php foreach (['CONFIRMED'=>'Confirmar','COMPLETED'=>'Completar','NO_SHOW'=>'No-show','CANCELLED'=>'Cancelar'] as $s => $label): ?>
                    <form method="POST" action="/dashboard/bookings/<?= e($booking['id']) ?>/status">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="<?= $s ?>">
                        <button class="px-3 py-1.5 text-xs rounded-lg border border-slate-300 hover:bg-white"><?= $label ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
            <form method="POST" action="/dashboard/bookings/<?= e($booking['id']) ?>/delete" class="mt-3" onsubmit="return confirm('¿Eliminar turno?');">
                <?= csrf_field() ?>
                <button class="text-red-600 text-xs hover:underline">Eliminar turno definitivamente</button>
            </form>
        </div>
    </div>
</div>
<?php View::endSection(); ?>
