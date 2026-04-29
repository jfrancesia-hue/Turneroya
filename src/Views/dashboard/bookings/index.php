<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <h2 class="text-2xl font-bold text-slate-800">Turnos</h2>
    <a href="/dashboard/bookings/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium">+ Nuevo turno</a>
</div>

<div class="flex gap-2 mb-4 text-sm">
    <?php foreach (['upcoming' => 'Próximos', 'today' => 'Hoy', 'past' => 'Pasados'] as $key => $label): ?>
        <a href="?filter=<?= $key ?>" class="px-3 py-1.5 rounded-lg <?= $filter === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php $pagination = $pagination ?? null; ?>
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($bookings)): ?>
        <div class="p-12 text-center text-slate-400">No hay turnos en este filtro.</div>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="text-left px-5 py-3">Fecha</th>
                    <th class="text-left px-5 py-3">Cliente</th>
                    <th class="text-left px-5 py-3 hidden md:table-cell">Servicio</th>
                    <th class="text-left px-5 py-3 hidden lg:table-cell">Profesional</th>
                    <th class="text-left px-5 py-3">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($bookings as $b): ?>
                <tr class="hover:bg-slate-50 cursor-pointer" onclick="window.location='/dashboard/bookings/<?= e($b['id']) ?>'">
                    <td class="px-5 py-3"><?= format_date($b['date'], 'd/m') ?> · <?= e(substr($b['start_time'], 0, 5)) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($b['client_name']) ?></td>
                    <td class="px-5 py-3 hidden md:table-cell text-slate-500"><?= e($b['service_name']) ?></td>
                    <td class="px-5 py-3 hidden lg:table-cell text-slate-500"><?= e($b['professional_name'] ?? '—') ?></td>
                    <td class="px-5 py-3">
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
                        <span class="px-2 py-0.5 rounded-full text-xs <?= $map[$b['status']] ?? 'bg-slate-100' ?>"><?= e($b['status']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($pagination && $pagination['pages'] > 1): ?>
        <?php
            $page = (int) $pagination['page'];
            $pages = (int) $pagination['pages'];
            $total = (int) $pagination['total'];
            $perPage = (int) $pagination['per_page'];
            $from = ($page - 1) * $perPage + 1;
            $to = min($page * $perPage, $total);
            $prev = max(1, $page - 1);
            $next = min($pages, $page + 1);
            $base = '?filter=' . urlencode($filter);
        ?>
        <div class="flex items-center justify-between gap-3 px-5 py-3 border-t border-slate-100 text-sm">
            <div class="text-slate-500">
                Mostrando <strong class="text-slate-700"><?= $from ?>–<?= $to ?></strong> de <strong class="text-slate-700"><?= $total ?></strong>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="<?= $base ?>&page=<?= $prev ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">← Anterior</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-300 cursor-not-allowed">← Anterior</span>
                <?php endif; ?>
                <span class="px-3 py-1.5 text-slate-500">Página <strong class="text-slate-700"><?= $page ?></strong> de <?= $pages ?></span>
                <?php if ($page < $pages): ?>
                    <a href="<?= $base ?>&page=<?= $next ?>" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">Siguiente →</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-300 cursor-not-allowed">Siguiente →</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php View::endSection(); ?>
