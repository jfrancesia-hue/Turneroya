<?php use TurneroYa\Core\View; use TurneroYa\Core\Session; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<title><?= e($title ?? 'Dashboard') ?> · Reservia</title>
<?php View::partial('partials/head'); ?>
<?= View::yield('head') ?>
</head>
<body class="dash-body text-ink-900 antialiased">
<div x-data="{ sidebarOpen: false }" class="dash-shell min-h-screen flex">
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" x-transition.opacity class="lg:hidden fixed inset-0 bg-ink-900/50 backdrop-blur-sm z-30"></div>

    <aside class="dash-sidebar fixed lg:sticky top-0 inset-y-0 left-0 z-40 w-72 transform lg:transform-none transition-transform duration-200 ease-out flex flex-col h-screen"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="dash-brand h-[72px] flex items-center px-6">
            <?php View::partial('partials/brand_logo', ['variant' => 'light']); ?>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
            <?php
            $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $groups = [
                'Operación' => [
                    ['/dashboard', 'Inicio', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['/dashboard/calendar', 'Calendario', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['/dashboard/bookings', 'Turnos', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['/dashboard/clients', 'Clientes', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ],
                'Configuración' => [
                    ['/dashboard/professionals', 'Profesionales', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['/dashboard/services', 'Servicios', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['/dashboard/schedules', 'Horarios', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['/dashboard/blockouts', 'Bloqueos', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                ],
                'Crecimiento' => [
                    ['/dashboard/analytics', 'Analytics', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    ['/dashboard/bot/config', 'Bot WhatsApp', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'AI'],
                    ['/dashboard/settings', 'Ajustes', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['/dashboard/billing', 'Facturación', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ],
            ];
            ?>
            <?php foreach ($groups as $groupName => $links): ?>
                <div>
                    <div class="dash-nav-label px-3 mb-2 text-[10px] font-bold uppercase"><?= e($groupName) ?></div>
                    <div class="space-y-1">
                        <?php foreach ($links as $link):
                            [$href, $label, $icon] = $link;
                            $badge = $link[3] ?? null;
                            $active = $href === '/dashboard' ? $currentPath === '/dashboard' : str_starts_with($currentPath, $href);
                        ?>
                            <a href="<?= e($href) ?>" class="dash-nav-link <?= $active ? 'is-active' : '' ?> group relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition">
                                <?php if ($active): ?><div class="absolute left-0 top-2 bottom-2 w-0.5 bg-lime-300 rounded-r-full"></div><?php endif; ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
                                <span class="flex-1"><?= e($label) ?></span>
                                <?php if ($badge): ?><span class="dash-ai-badge"><?= e($badge) ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <?php
        $currentPlan = null;
        $currentSub = null;
        if (\TurneroYa\Core\Auth::businessId()) {
            try {
                $currentPlan = \TurneroYa\Models\Subscription::currentPlanFor(\TurneroYa\Core\Auth::businessId());
                $currentSub = \TurneroYa\Models\Subscription::activeForBusiness(\TurneroYa\Core\Auth::businessId());
            } catch (\Throwable $e) {}
        }
        $planName = $currentPlan['name'] ?? 'Free';
        $showUpgrade = !$currentPlan || (($currentPlan['id'] ?? 'FREE') !== 'NEGOCIO' && ($currentPlan['id'] ?? 'FREE') !== 'MULTI_SUCURSAL');
        ?>
        <div class="p-4 dash-sidebar-bottom">
            <?php if ($showUpgrade): ?>
                <a href="/dashboard/billing" class="dash-upgrade block relative overflow-hidden p-4 text-white transition">
                    <div class="relative">
                        <div class="text-xs font-bold uppercase tracking-wider opacity-80">Plan <?= e($planName) ?></div>
                        <div class="mt-1 text-sm font-semibold"><?= ($currentSub['status'] ?? '') === 'TRIALING' ? 'Prueba activa' : 'Desbloqueá el Bot IA' ?></div>
                        <div class="mt-3 w-full text-center px-3 py-1.5 bg-white text-brand-700 rounded-lg text-xs font-bold">Upgrade →</div>
                    </div>
                </a>
            <?php else: ?>
                <a href="/dashboard/billing" class="block rounded-lg border border-white/10 p-4 hover:bg-white/10 transition">
                    <div class="text-xs font-bold uppercase tracking-wider text-white/50">Plan <?= e($planName) ?></div>
                    <div class="mt-1 text-sm font-semibold text-white">Gestionar facturación</div>
                </a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="flex-1 min-w-0 flex flex-col">
        <header class="dash-topbar sticky top-0 z-20 h-[72px] flex items-center justify-between px-4 lg:px-8">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden w-10 h-10 rounded-lg hover:bg-ink-100 flex items-center justify-center text-ink-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <h1 class="text-lg font-bold text-ink-900"><?= e($pageTitle ?? $title ?? '') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?><p class="text-xs text-ink-500"><?= e($pageSubtitle) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="/dashboard/bookings/create" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 bg-ink-900 text-white rounded-lg text-sm font-bold hover:bg-brand-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/></svg>
                    Nuevo turno
                </a>
                <button class="hidden md:flex items-center gap-2 px-3 py-2 bg-white/70 hover:bg-white rounded-lg text-sm text-ink-500 border border-ink-200/70 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Buscar</span>
                </button>
                <div x-data="{open:false}" class="relative">
                    <button @click="open=!open" class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-lg hover:bg-white/70 transition">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white font-bold text-sm shadow-brand">
                            <?= strtoupper(substr((string) (auth()['name'] ?? 'U'), 0, 1)) ?>
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-sm font-semibold text-ink-900 leading-tight"><?= e(auth()['name'] ?? 'Usuario') ?></div>
                            <div class="text-[11px] text-ink-500 leading-tight"><?= e(auth()['email'] ?? '') ?></div>
                        </div>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-transition x-cloak class="absolute right-0 top-full mt-2 w-56 bg-white rounded-lg shadow-lift border border-ink-200/70 py-1.5 z-30">
                        <a href="/dashboard/settings" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">Ajustes</a>
                        <div class="my-1 border-t border-ink-100"></div>
                        <form method="POST" action="/logout">
                            <?= csrf_field() ?>
                            <button type="submit" class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <?php foreach (Session::allFlash() as $type => $msg):
            if ($type === '_old_input') continue; ?>
            <div class="mx-4 lg:mx-8 mt-6 px-4 py-3 rounded-lg text-sm flex items-start gap-2 <?= $type === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' ?>">
                <span><?= e($msg) ?></span>
            </div>
        <?php endforeach; ?>

        <main class="dash-main flex-1 p-4 lg:p-8">
            <?= View::yield('content') ?>
        </main>
    </div>
</div>
</body>
</html>
