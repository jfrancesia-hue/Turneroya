<?php use TurneroYa\Core\View; use TurneroYa\Core\Session; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<title><?= e($title ?? 'Reservia') ?></title>
<?php View::partial('partials/head'); ?>
</head>
<body class="bg-white text-ink-900 antialiased min-h-screen">
<div class="min-h-screen grid lg:grid-cols-[1.08fr_1fr]">

    <aside class="hidden lg:flex relative overflow-hidden bg-ink-950 text-white p-12 flex-col justify-between">
        <div class="absolute inset-0 bg-grid-dark opacity-40"></div>
        <div class="absolute inset-0" style="background: radial-gradient(circle at 18% 18%, rgb(198 244 50 / .14), transparent 26%), radial-gradient(circle at 82% 70%, rgb(0 168 132 / .22), transparent 30%), linear-gradient(135deg, rgb(7 17 31), rgb(7 94 84 / .76));"></div>

        <div class="relative z-10">
            <?php View::partial('partials/brand_logo', ['variant' => 'light']); ?>
        </div>

        <div class="relative z-10 space-y-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#C6F432] animate-pulse"></span>
                    Recepcionista IA para WhatsApp
                </div>
                <h2 class="mt-6 text-4xl font-extrabold leading-tight">
                    Tu WhatsApp atiende.<br>Tu agenda se llena.
                </h2>
                <p class="mt-4 text-white/70 text-base max-w-md leading-relaxed">
                    Converti consultas en turnos confirmados con IA, MercadoPago y recordatorios automaticos.
                </p>
            </div>

            <div class="rounded-lg border border-white/10 bg-white/5 backdrop-blur p-4 max-w-sm shadow-2xl">
                <div class="flex items-center gap-2 pb-3 border-b border-white/10">
                    <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-xs font-bold">RV</div>
                    <div>
                        <div class="text-sm font-semibold">Reservia Bot</div>
                        <div class="text-[10px] text-[#C6F432] flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#C6F432]"></span> online
                        </div>
                    </div>
                </div>
                <div class="pt-3 space-y-2">
                    <div class="text-xs bg-white/10 rounded-lg px-3 py-2 inline-block max-w-[85%]">
                        Hola, quiero un corte de pelo el jueves
                    </div>
                    <div class="text-right">
                        <div class="text-xs bg-brand-600 rounded-lg px-3 py-2 inline-block max-w-[85%] text-left">
                            Tengo disponible con <b>Maria</b> jueves 15:00. Te lo confirmo?
                        </div>
                    </div>
                    <div class="text-xs bg-white/10 rounded-lg px-3 py-2 inline-block">
                        Si, dale
                    </div>
                    <div class="text-right">
                        <div class="text-xs bg-brand-600 rounded-lg px-3 py-2 inline-block max-w-[85%] text-left">
                            Listo. Turno #127 confirmado. Te recuerdo 24 h antes.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <blockquote class="text-sm text-white/80 italic">
                "Recuperamos horas de atencion y bajamos los plantones en el primer mes."
            </blockquote>
            <div class="mt-3 flex items-center gap-2 text-xs text-white/50">
                <div class="w-8 h-8 rounded-full bg-[#C6F432] flex items-center justify-center text-[10px] font-bold text-ink-950">MG</div>
                <div>
                    <div class="font-semibold text-white/90">Maria Gutierrez</div>
                    <div>Belleza Pura, Palermo</div>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">
            <div class="lg:hidden mb-8 flex justify-center">
                <?php View::partial('partials/brand_logo', ['size' => 'lg']); ?>
            </div>

            <?php foreach (Session::allFlash() as $type => $msg):
                if ($type === '_old_input') continue; ?>
                <div class="mb-6 px-4 py-3 rounded-lg text-sm flex items-start gap-2 <?= $type === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' ?>">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <?php if ($type === 'error'): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <?php endif; ?>
                    </svg>
                    <span><?= e($msg) ?></span>
                </div>
            <?php endforeach; ?>

            <?= View::yield('content') ?>

            <p class="mt-10 text-center text-xs text-ink-400">
                &copy; <?= date('Y') ?> Reservia. Hecho en Argentina.
            </p>
        </div>
    </main>
</div>
</body>
</html>
