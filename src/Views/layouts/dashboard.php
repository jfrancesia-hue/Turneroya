<?php use TurneroYa\Core\View; use TurneroYa\Core\Session; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<title><?= e($title ?? 'Dashboard') ?> · TurneroYa</title>
<?php View::partial('partials/head'); ?>
<?= View::yield('head') ?>
</head>
<body class="bg-ink-50 text-ink-900 antialiased">
<div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

    <!-- Overlay mobile -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen=false" x-transition.opacity class="lg:hidden fixed inset-0 bg-ink-900/40 backdrop-blur-sm z-30"></div>

    <!-- Sidebar -->
    <aside
        class="fixed lg:sticky top-0 inset-y-0 left-0 z-40 w-72 bg-white border-r border-ink-200/70 transform lg:transform-none transition-transform duration-200 ease-out flex flex-col h-screen"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        <!-- Brand -->
        <div class="h-[72px] flex items-center px-6 border-b border-ink-200/70">
            <?php View::partial('partials/brand_logo'); ?>
        </div>

        <!-- Navigation -->
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
                ],
            ];
            ?>
            <?php foreach ($groups as $groupName => $links): ?>
                <div>
                    <div class="px-3 mb-2 text-[10px] font-bold uppercase tracking-wider text-ink-400"><?= e($groupName) ?></div>
                    <div class="space-y-0.5">
                        <?php foreach ($links as $link):
                            [$href, $label, $icon] = $link;
                            $badge = $link[3] ?? null;
                            $active = $href === '/dashboard' ? $currentPath === '/dashboard' : str_starts_with($currentPath, $href);
                        ?>
                            <a href="<?= e($href) ?>"
                               class="<?= $active ? 'bg-brand-50 text-brand-700 shadow-soft' : 'text-ink-600 hover:bg-ink-50 hover:text-ink-900' ?> group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition">
                                <?php if ($active): ?>
                                    <div class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-brand-600 rounded-r-full"></div>
                                <?php endif; ?>
                                <svg class="w-5 h-5 <?= $active ? 'text-brand-600' : 'text-ink-400 group-hover:text-ink-600' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/>
                                </svg>
                                <span class="flex-1"><?= e($label) ?></span>
                                <?php if ($badge): ?>
                                    <span class="px-1.5 py-0.5 rounded-md bg-gradient-to-r from-brand-600 to-accent-600 text-white text-[9px] font-bold tracking-wider"><?= e($badge) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- Bottom: upgrade card -->
        <div class="p-4 border-t border-ink-200/70">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700 p-4 text-white">
                <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
                <div class="relative">
                    <div class="text-xs font-bold uppercase tracking-wider opacity-80">Plan Starter</div>
                    <div class="mt-1 text-sm font-semibold">Desbloqueá el Bot IA</div>
                    <button class="mt-3 w-full px-3 py-1.5 bg-white text-brand-700 rounded-lg text-xs font-bold hover:bg-brand-50 transition">
                        Upgrade →
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main column -->
    <div class="flex-1 min-w-0 flex flex-col">
        <!-- Topbar -->
        <header class="sticky top-0 z-20 h-[72px] bg-white/80 backdrop-blur-lg border-b border-ink-200/70 flex items-center justify-between px-4 lg:px-8">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden w-10 h-10 rounded-lg hover:bg-ink-100 flex items-center justify-center text-ink-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <h1 class="text-lg font-bold text-ink-900 tracking-tight"><?= e($pageTitle ?? $title ?? '') ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <p class="text-xs text-ink-500"><?= e($pageSubtitle) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Search -->
                <button class="hidden md:flex items-center gap-2 px-3 py-2 bg-ink-100 hover:bg-ink-200 rounded-lg text-sm text-ink-500 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Buscar...</span>
                    <kbd class="px-1.5 py-0.5 text-[10px] bg-white border border-ink-200 rounded">⌘K</kbd>
                </button>

                <!-- Notifications -->
                <button class="w-10 h-10 rounded-lg hover:bg-ink-100 flex items-center justify-center text-ink-600 relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.16V11a6 6 0 00-4-5.66V5a2 2 0 10-4 0v.34C7.67 6.16 6 8.39 6 11v3.16c0 .53-.21 1.05-.59 1.43L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
                    <div class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></div>
                </button>

                <!-- User menu -->
                <div x-data="{open:false}" class="relative">
                    <button @click="open=!open" class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-xl hover:bg-ink-50 transition">
                        <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-white font-bold text-sm shadow-brand">
                            <?= strtoupper(substr((string) (auth()['name'] ?? 'U'), 0, 1)) ?>
                        </div>
                        <div class="hidden sm:block text-left">
                            <div class="text-sm font-semibold text-ink-900 leading-tight"><?= e(auth()['name'] ?? 'Usuario') ?></div>
                            <div class="text-[11px] text-ink-500 leading-tight"><?= e(auth()['email'] ?? '') ?></div>
                        </div>
                        <svg class="hidden sm:block w-4 h-4 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open=false" x-transition x-cloak class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lift border border-ink-200/70 py-1.5 z-30">
                        <a href="/dashboard/settings" class="flex items-center gap-2 px-3 py-2 text-sm text-ink-700 hover:bg-ink-50">
                            <svg class="w-4 h-4 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.3a1.7 1.7 0 013.4 0 1.7 1.7 0 002.6 1 1.7 1.7 0 012.4 2.4 1.7 1.7 0 001 2.6 1.7 1.7 0 010 3.4 1.7 1.7 0 00-1 2.6 1.7 1.7 0 01-2.4 2.4 1.7 1.7 0 00-2.6 1 1.7 1.7 0 01-3.4 0 1.7 1.7 0 00-2.6-1 1.7 1.7 0 01-2.4-2.4 1.7 1.7 0 00-1-2.6 1.7 1.7 0 010-3.4 1.7 1.7 0 001-2.6 1.7 1.7 0 012.4-2.4 1.7 1.7 0 002.6-1zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Ajustes
                        </a>
                        <div class="my-1 border-t border-ink-100"></div>
                        <form method="POST" action="/logout">
                            <?= csrf_field() ?>
                            <button type="submit" class="flex items-center gap-2 w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash messages -->
        <?php foreach (Session::allFlash() as $type => $msg):
            if ($type === '_old_input') continue; ?>
            <div class="mx-4 lg:mx-8 mt-6 px-4 py-3 rounded-xl text-sm flex items-start gap-2 <?= $type === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' ?>">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <?php if ($type === 'error'): ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.94 4h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                    <?php else: ?>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    <?php endif; ?>
                </svg>
                <span><?= e($msg) ?></span>
            </div>
        <?php endforeach; ?>

        <main class="flex-1 p-4 lg:p-8">
            <?= View::yield('content') ?>
        </main>
    </div>
</div>
</body>
</html>
