<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Profesionales</h2>
        <p class="text-slate-500 text-sm">Gestioná tu equipo</p>
    </div>
    <a href="/dashboard/professionals/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">+ Nuevo profesional</a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <?php if (empty($professionals)): ?>
        <div class="p-12 text-center text-slate-400">Aún no hay profesionales cargados.</div>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3">Nombre</th>
                    <th class="text-left px-5 py-3 hidden md:table-cell">Especialización</th>
                    <th class="text-left px-5 py-3 hidden md:table-cell">Contacto</th>
                    <th class="text-left px-5 py-3">Estado</th>
                    <th class="px-5 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($professionals as $p): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-semibold text-sm" style="background-color: <?= e($p['color']) ?>">
                                <?= strtoupper(substr($p['name'], 0, 1)) ?>
                            </div>
                            <div class="font-medium"><?= e($p['name']) ?></div>
                        </div>
                    </td>
                    <td class="px-5 py-4 hidden md:table-cell text-slate-500"><?= e($p['specialization'] ?? '—') ?></td>
                    <td class="px-5 py-4 hidden md:table-cell text-slate-500"><?= e($p['phone'] ?? $p['email'] ?? '—') ?></td>
                    <td class="px-5 py-4">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs <?= $p['is_active'] ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' ?>">
                            <?= $p['is_active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a href="/dashboard/professionals/<?= e($p['id']) ?>/edit" class="text-indigo-600 hover:underline text-xs">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php View::endSection(); ?>
