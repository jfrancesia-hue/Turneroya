<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<?php
$dayLabels = [
    0 => 'Domingo',
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
];

$renderForm = function (?string $professionalId, string $label, array $schedules) use ($dayLabels): void {
    $byDay = [];
    foreach ($schedules as $s) $byDay[(int) $s['day_of_week']] = $s;
    ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-6">
        <h3 class="font-semibold text-slate-700 mb-4"><?= e($label) ?></h3>
        <form method="POST" action="/dashboard/schedules" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="professional_id" value="<?= e($professionalId ?? '') ?>">
            <?php foreach ($dayLabels as $dow => $name):
                $d = $byDay[$dow] ?? null;
                $active = $d !== null; ?>
                <div class="grid grid-cols-12 items-center gap-2 text-sm">
                    <div class="col-span-3 sm:col-span-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="day_<?= $dow ?>_active" value="1" <?= $active ? 'checked' : '' ?>>
                            <span class="font-medium"><?= e($name) ?></span>
                        </label>
                    </div>
                    <div class="col-span-4 sm:col-span-2">
                        <input type="time" name="day_<?= $dow ?>_start" value="<?= e($d['start_time'] ?? '09:00') ?>" class="w-full px-2 py-1.5 rounded border border-slate-300 text-sm">
                    </div>
                    <div class="col-span-5 sm:col-span-2">
                        <input type="time" name="day_<?= $dow ?>_end" value="<?= e($d['end_time'] ?? '18:00') ?>" class="w-full px-2 py-1.5 rounded border border-slate-300 text-sm">
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <input type="time" name="day_<?= $dow ?>_break_start" value="<?= e($d['break_start'] ?? '') ?>" placeholder="Break inicio" class="w-full px-2 py-1.5 rounded border border-slate-300 text-sm">
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <input type="time" name="day_<?= $dow ?>_break_end" value="<?= e($d['break_end'] ?? '') ?>" placeholder="Break fin" class="w-full px-2 py-1.5 rounded border border-slate-300 text-sm">
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Guardar horarios</button>
        </form>
    </div>
<?php };
?>

<h2 class="text-2xl font-bold text-slate-800 mb-2">Horarios de atención</h2>
<p class="text-slate-500 text-sm mb-6">Los horarios por profesional tienen prioridad sobre los del negocio.</p>

<?php $renderForm(null, 'Horario general del negocio (default)', $businessSchedules); ?>

<?php foreach ($professionals as $p): ?>
    <?php $renderForm($p['id'], 'Horario de ' . $p['name'], $proSchedules[$p['id']] ?? []); ?>
<?php endforeach; ?>
<?php View::endSection(); ?>
