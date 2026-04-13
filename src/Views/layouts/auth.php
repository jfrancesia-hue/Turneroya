<?php use TurneroYa\Core\View; use TurneroYa\Core\Session; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<title><?= e($title ?? 'TurneroYa') ?></title>
<?php View::partial('partials/head'); ?>
</head>
<body class="bg-white text-ink-900 antialiased min-h-screen">
<div class="min-h-screen grid lg:grid-cols-[1.1fr_1fr]">

    <!-- Brand panel izquierdo -->
    <aside class="hidden lg:flex relative overflow-hidden bg-ink-950 text-white p-12 flex-col justify-between">
        <!-- Background decoration -->
        <div class="absolute inset-0 bg-grid-dark opacity-40"></div>
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-brand-600 rounded-full blur-3xl opacity-30"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-accent-600 rounded-full blur-3xl opacity-20"></div>

        <!-- Top: logo -->
        <div class="relative z-10">
            <?php $variant = 'light'; View::partial('partials/brand_logo'); ?>
        </div>

        <!-- Middle: gran mensaje + mockup -->
        <div class="relative z-10 space-y-10">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-semibold uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Plataforma enterprise
                </div>
                <h2 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight">
                    Tu agenda, en<br>piloto automático.
                </h2>
                <p class="mt-4 text-white/60 text-base max-w-md leading-relaxed">
                    Chatbot de WhatsApp con IA que toma turnos mientras vos trabajás.
                    Más de 2.500 negocios en LATAM confían en nosotros.
                </p>
            </div>

            <!-- Mini mockup de chat WhatsApp -->
            <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur p-4 max-w-sm shadow-2xl">
                <div class="flex items-center gap-2 pb-3 border-b border-white/10">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-brand-500 to-accent-500 flex items-center justify-center text-xs font-bold">T</div>
                    <div>
                        <div class="text-sm font-semibold">TurneroYa Bot</div>
                        <div class="text-[10px] text-emerald-400 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> online
                        </div>
                    </div>
                </div>
                <div class="pt-3 space-y-2">
                    <div class="text-xs bg-white/10 rounded-2xl rounded-bl-sm px-3 py-2 inline-block max-w-[85%]">
                        Hola, quiero un corte de pelo el jueves 👋
                    </div>
                    <div class="text-right">
                        <div class="text-xs bg-brand-600 rounded-2xl rounded-br-sm px-3 py-2 inline-block max-w-[85%] text-left">
                            ¡Hola! Tengo disponible con <b>María</b> jueves 15:00hs. ¿Te lo confirmo? ✨
                        </div>
                    </div>
                    <div class="text-xs bg-white/10 rounded-2xl rounded-bl-sm px-3 py-2 inline-block">
                        Sí, dale
                    </div>
                    <div class="text-right">
                        <div class="text-xs bg-brand-600 rounded-2xl rounded-br-sm px-3 py-2 inline-block max-w-[85%] text-left">
                            Listo ✅ Turno #127 confirmado. Te recuerdo 24hs antes.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom: testimonial -->
        <div class="relative z-10">
            <blockquote class="text-sm text-white/80 italic">
                "Recuperamos 12 horas semanales. Facturamos 30% más."
            </blockquote>
            <div class="mt-3 flex items-center gap-2 text-xs text-white/50">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-400 to-rose-500 flex items-center justify-center text-[10px] font-bold text-white">MG</div>
                <div>
                    <div class="font-semibold text-white/90">María Gutiérrez</div>
                    <div>Belleza Pura, Palermo</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Form panel derecho -->
    <main class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">
            <!-- Logo mobile (sólo visible cuando no se ve el brand panel) -->
            <div class="lg:hidden mb-8 flex justify-center">
                <?php View::partial('partials/brand_logo', ['size' => 'lg']); ?>
            </div>

            <?php foreach (Session::allFlash() as $type => $msg):
                if ($type === '_old_input') continue; ?>
                <div class="mb-6 px-4 py-3 rounded-xl text-sm flex items-start gap-2 <?= $type === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' ?>">
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
                © <?= date('Y') ?> TurneroYa · Hecho con ♥ en Argentina
            </p>
        </div>
    </main>
</div>
</body>
</html>
