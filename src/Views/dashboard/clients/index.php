<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Clientes</h2>
    </div>
    <div class="flex gap-2">
        <form method="GET" class="flex">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar..." class="px-3 py-2 rounded-l-lg border border-slate-300 text-sm">
            <button class="px-3 py-2 bg-slate-800 text-white rounded-r-lg text-sm">Buscar</button>
        </form>
        <a href="/dashboard/clients/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">+ Nuevo</a>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($clients)): ?>
        <div class="p-12 text-center text-slate-400">Sin clientes aún.</div>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3">Nombre</th>
                    <th class="text-left px-5 py-3 hidden md:table-cell">Teléfono</th>
                    <th class="text-left px-5 py-3 hidden lg:table-cell">Email</th>
                    <th class="text-center px-5 py-3">Turnos</th>
                    <th class="text-center px-5 py-3">No-show</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($clients as $c): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3">
                        <a href="/dashboard/clients/<?= e($c['id']) ?>" class="font-medium text-indigo-600 hover:underline"><?= e($c['name']) ?></a>
                    </td>
                    <td class="px-5 py-3 hidden md:table-cell text-slate-500"><?= e($c['phone'] ?? '—') ?></td>
                    <td class="px-5 py-3 hidden lg:table-cell text-slate-500"><?= e($c['email'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center"><?= (int) $c['total_bookings'] ?></td>
                    <td class="px-5 py-3 text-center">
                        <?php if ((int) $c['no_show_count'] > 0): ?>
                            <span class="text-red-600 font-semibold"><?= (int) $c['no_show_count'] ?></span>
                        <?php else: ?>
                            <span class="text-slate-400">0</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php View::endSection(); ?>
