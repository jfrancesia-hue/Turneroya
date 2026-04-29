<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<!-- ============================================================
     STICKY NAVIGATION (con hamburger bajo md)
     ============================================================ -->
<nav x-data="{scrolled:false, open:false}"
     @scroll.window="scrolled = window.scrollY > 20"
     :class="scrolled ? 'glass border-b border-ink-200/60' : ''"
     class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-6 h-[72px] flex items-center justify-between">
        <?php View::partial('partials/brand_logo'); ?>

        <!-- Links desktop (≥ 1024) -->
        <div class="hidden lg:flex items-center gap-8">
            <a href="#features" class="text-sm font-medium text-ink-600 hover:text-ink-900 transition">Funcionalidades</a>
            <a href="#pricing" class="text-sm font-medium text-ink-600 hover:text-ink-900 transition">Precios</a>
            <a href="#testimonials" class="text-sm font-medium text-ink-600 hover:text-ink-900 transition">Testimonios</a>
            <a href="#industries" class="text-sm font-medium text-ink-600 hover:text-ink-900 transition">Para quién</a>
        </div>

        <div class="flex items-center gap-2">
            <a href="/login" class="hidden sm:block text-sm font-semibold text-ink-700 hover:text-ink-900 px-3 py-2">Iniciar sesión</a>
            <a href="/register" class="btn-press hidden lg:inline-flex items-center px-4 py-2.5 bg-ink-900 text-white rounded-xl text-sm font-semibold hover:bg-ink-800 shadow-soft">
                Empezar gratis
            </a>

            <!-- Hamburger (bajo lg) -->
            <button type="button"
                    class="nav-burger lg:!hidden"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-controls="mobile-nav"
                    aria-label="Abrir menú">
                <svg x-show="!open" class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="open" x-cloak class="w-5 h-5 text-ink-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Menú mobile desplegable (bajo lg) -->
    <div id="mobile-nav" class="lg:hidden" x-show="open" x-cloak x-transition.opacity>
        <div class="nav-mobile">
            <a href="#features" @click="open=false">Funcionalidades</a>
            <a href="#pricing" @click="open=false">Precios</a>
            <a href="#testimonials" @click="open=false">Testimonios</a>
            <a href="#industries" @click="open=false">Para quién</a>
            <a href="/login" @click="open=false">Iniciar sesión</a>
            <a href="/register" @click="open=false" class="!bg-ink-900 !text-white text-center mt-1">Empezar gratis</a>
        </div>
    </div>
</nav>

<!-- ============================================================
     HERO clipeado · video + content + dashboard preview embebido
     El border-radius del shell recorta las cards del dashboard:
     bleedean por la parte inferior por el margin-bottom negativo.
     ============================================================ -->
<!-- Scroll progress bar -->
<div class="scroll-progress" aria-hidden="true"></div>

<section class="hero-shell" style="margin-top: 88px;">
    <!-- Aurora / mesh animado (capa más profunda) -->
    <div class="aurora" aria-hidden="true">
        <span></span><span></span><span></span><span></span>
    </div>

    <!-- Video background nativo (loop, muted, sin animaciones custom) -->
    <video class="hero-video"
           autoplay
           muted
           loop
           playsinline
           preload="metadata"
           poster="/assets/img/hero-poster.svg"
           aria-hidden="true">
        <source src="/assets/img/hero.mp4" type="video/mp4">
    </video>
    <div class="hero-grid"></div>
    <div class="hero-veil"></div>
    <div class="grain" aria-hidden="true"></div>
    <div class="hero-spotlight" aria-hidden="true"></div>

    <!-- Contenido -->
    <div class="hero-content max-w-7xl mx-auto">
        <div class="text-center max-w-3xl mx-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 bg-white/10 backdrop-blur">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                <span class="hero-eyebrow font-bold uppercase text-white/90">Plataforma de turnos + IA</span>
            </div>

            <h1 class="hero-title font-display mt-6 text-white" data-reveal>
                El asistente de tu negocio<br>
                que <span class="font-serif-italic text-gradient">nunca duerme</span>.
            </h1>

            <p class="hero-subtitle mt-6 text-white/80 max-w-2xl mx-auto">
                Chatbot de WhatsApp con IA que toma turnos, confirma, recuerda y reprograma automáticamente.
                Tu agenda llena mientras vos trabajás.
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3" data-reveal style="--reveal-delay: 120ms">
                <a href="/register" class="hero-cta cta-shimmer btn-press inline-flex items-center gap-2 bg-white text-ink-900 rounded-xl font-semibold shadow-lift hover:bg-brand-50 transition">
                    Empezar 14 días gratis
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="#features" class="hero-cta btn-press inline-flex items-center gap-2 bg-white/10 text-white border border-white/25 rounded-xl font-semibold backdrop-blur hover:bg-white/15 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    Ver cómo funciona
                </a>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs text-white/70">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Sin tarjeta
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Setup en 3 minutos
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Soporte en español
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard preview embebido (1 → 2 → 3 cols) -->
    <div class="hero-dashboard max-w-7xl mx-auto">
        <div class="hero-dashboard-grid">

            <!-- Card 1: Turnos hoy -->
            <div class="hero-card" data-reveal>
                <div class="hero-card-head">
                    <span class="hero-card-title">Turnos hoy</span>
                    <span class="text-emerald-300 text-xs font-semibold">+<span data-countup="12" data-countup-suffix="%">12%</span></span>
                </div>
                <div class="hero-card-value" data-countup="38">38</div>
                <div class="hero-bars">
                    <span style="height: 35%"></span>
                    <span style="height: 55%"></span>
                    <span style="height: 70%"></span>
                    <span style="height: 50%"></span>
                    <span style="height: 85%"></span>
                    <span style="height: 95%"></span>
                    <span style="height: 65%"></span>
                </div>
                <div class="hero-card-foot">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Pico previsto a las 17:00
                </div>
            </div>

            <!-- Card 2: Próximos turnos -->
            <div class="hero-card" data-reveal style="--reveal-delay: 80ms">
                <div class="hero-card-head">
                    <span class="hero-card-title">Próximos</span>
                    <span class="text-white/60 text-xs">en vivo</span>
                </div>
                <div class="hero-timeline">
                    <div class="hero-timeline-row">
                        <span class="hero-timeline-dot" style="background:#A855F7"></span>
                        <strong>Corte · María</strong>
                        <span class="ml-auto">15:00</span>
                    </div>
                    <div class="hero-timeline-row">
                        <span class="hero-timeline-dot" style="background:#10B981"></span>
                        <strong>Color · Sofía</strong>
                        <span class="ml-auto">15:30</span>
                    </div>
                    <div class="hero-timeline-row">
                        <span class="hero-timeline-dot" style="background:#4F46E5"></span>
                        <strong>Brushing · Juli</strong>
                        <span class="ml-auto">16:00</span>
                    </div>
                    <div class="hero-timeline-row">
                        <span class="hero-timeline-dot" style="background:#EC4899"></span>
                        <strong>Manicura · Ana</strong>
                        <span class="ml-auto">16:30</span>
                    </div>
                </div>
                <div class="hero-card-foot">
                    <svg class="w-4 h-4 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Sincroniza solo
                </div>
            </div>

            <!-- Card 3: Tasa de asistencia (gauge) -->
            <div class="hero-card" data-reveal style="--reveal-delay: 160ms">
                <div class="hero-card-head">
                    <span class="hero-card-title">Tasa asistencia</span>
                    <span class="text-emerald-300 text-xs font-semibold">−<span data-countup="82" data-countup-suffix="%">82%</span> no-show</span>
                </div>
                <div class="flex items-center gap-4 mt-3">
                    <div class="hero-gauge" style="--pct: 94"><strong><span data-countup="94" data-countup-suffix="%">94%</span></strong></div>
                    <div class="text-sm text-white/75 leading-snug">
                        <div class="font-semibold text-white">Recordatorios IA</div>
                        Confirmación automática 24 hs antes del turno por WhatsApp.
                    </div>
                </div>
                <div class="hero-card-foot">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Powered by Claude
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Compensa el bleed negativo del dashboard sin que la siguiente sección se monte encima -->
<div class="hero-spacer" aria-hidden="true"></div>

<!-- ============================================================
     TRUST LOGO BAR
     ============================================================ -->
<section class="border-y border-ink-200/70 bg-ink-50/50 py-12">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center text-xs font-bold uppercase tracking-[0.2em] text-ink-500 mb-8">
            Elegido por más de <span data-countup="2547">2.547</span> negocios en Latinoamérica
        </div>
        <?php $logos = ['Belleza Pura','Clínica Norte','Gym Palermo','Dental Care','VetLife','Studio 42','Salón Aurora','Centro Médico Sur','PetCare','Pilates Lab']; ?>
        <div class="marquee">
            <div class="marquee-track">
                <?php for ($i = 0; $i < 2; $i++): ?>
                    <?php foreach ($logos as $logo): ?>
                        <div class="marquee-item"><?= e($logo) ?></div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FEATURES GRID 3x2
     ============================================================ -->
<section id="features" class="py-24">
    <div class="max-w-7xl mx-auto px-6">
        <div class="max-w-2xl mx-auto text-center mb-16" data-reveal>
            <div class="inline-block text-[11px] font-bold uppercase tracking-wider text-brand-600 mb-3">Funcionalidades</div>
            <h2 class="text-display-sm font-display text-ink-900">
                Todo lo que tu negocio necesita,<br>en un solo lugar.
            </h2>
            <p class="mt-4 text-ink-600 text-lg">Sin integraciones que no funcionan, sin hojas de Excel, sin WhatsApps perdidos.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php
            $features = [
                [
                    'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                    'title' => 'Bot de WhatsApp con IA',
                    'desc' => 'Chatbot powered by Claude que entiende lenguaje natural y responde 24/7 en nombre de tu negocio.',
                    'tag' => 'Powered by Claude',
                    'tagColor' => 'bg-gradient-to-r from-brand-600 to-accent-600 text-white',
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'title' => 'Calendario inteligente',
                    'desc' => 'Multi-profesional, sin doble turnos jamás. Gestiona horarios, breaks, vacaciones y feriados sin esfuerzo.',
                ],
                [
                    'icon' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.16V11a6 6 0 00-4-5.66V5a2 2 0 10-4 0v.34C7.67 6.16 6 8.39 6 11v3.16c0 .53-.2 1.05-.59 1.43L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9',
                    'title' => 'Recordatorios automáticos',
                    'desc' => 'Envío por WhatsApp 24hs antes de cada turno. Reduce no-shows hasta un 82% sin que muevas un dedo.',
                ],
                [
                    'icon' => 'M13.8 10.2L21 3m0 0h-5.5M21 3v5.5M3 3l6.8 6.8M3 3v5.5M3 3h5.5m0 12.2L3 21m0 0v-5.5M3 21h5.5m6.8-5.2L21 21m0 0v-5.5M21 21h-5.5',
                    'title' => 'Página pública de reservas',
                    'desc' => 'Tu propio link de reservas en segundos: turneroya.com/tu-negocio. Compartilo por Instagram, web o tarjetas.',
                ],
                [
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'title' => 'Pagos con MercadoPago',
                    'desc' => 'Cobrá señas al momento de reservar. Cero ghosting, clientes 100% comprometidos con el turno.',
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'title' => 'Analytics en tiempo real',
                    'desc' => 'Horarios pico, servicios top, ingresos por profesional, fuentes de reservas. Datos para decidir mejor.',
                ],
            ];
            foreach ($features as $i => $f):
                $isKiller = !empty($f['tag']);
                $extra = $isKiller ? 'killer-card' : 'spot-card';
                $delay = ($i * 70) . 'ms';
            ?>
                <div data-reveal style="--reveal-delay: <?= $delay ?>" class="group relative rounded-2xl bg-white border border-ink-200/70 p-6 hover:border-brand-300 hover:shadow-elev transition-all <?= $extra ?>">
                    <div class="flex items-start justify-between">
                        <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center group-hover:bg-brand-100 transition">
                            <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $f['icon'] ?>"/>
                            </svg>
                        </div>
                        <?php if (!empty($f['tag'])): ?>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-1 rounded-md <?= $f['tagColor'] ?? 'bg-ink-100 text-ink-700' ?>">
                                <?= e($f['tag']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-ink-900"><?= e($f['title']) ?></h3>
                    <p class="mt-2 text-sm text-ink-600 leading-relaxed"><?= e($f['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     INDUSTRIES
     ============================================================ -->
<section id="industries" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12" data-reveal>
            <div class="inline-block text-[11px] font-bold uppercase tracking-wider text-brand-600 mb-3">Para quién es</div>
            <h2 class="text-display-sm font-display text-ink-900">Funciona con cualquier negocio de servicios</h2>
        </div>
        <div class="flex flex-wrap justify-center gap-3 max-w-3xl mx-auto" data-reveal style="--reveal-delay: 80ms">
            <?php
            $industries = [
                ['💇', 'Peluquerías'],
                ['🏥', 'Consultorios médicos'],
                ['💪', 'Gimnasios'],
                ['🦷', 'Odontólogos'],
                ['🐶', 'Veterinarias'],
                ['🔧', 'Talleres mecánicos'],
                ['📸', 'Estudios fotográficos'],
                ['⚖️', 'Estudios jurídicos'],
                ['💰', 'Contadores'],
                ['🎨', 'Estética'],
                ['🧘', 'Yoga / Pilates'],
                ['🐾', 'Peluquería canina'],
            ];
            foreach ($industries as [$emoji, $name]): ?>
                <div class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-ink-50 hover:bg-brand-50 border border-ink-200/70 hover:border-brand-200 transition cursor-default">
                    <span class="text-base"><?= $emoji ?></span>
                    <span class="text-sm font-semibold text-ink-700"><?= e($name) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     TESTIMONIALS
     ============================================================ -->
<section id="testimonials" class="py-24 bg-ink-50/50">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16" data-reveal>
            <div class="inline-block text-[11px] font-bold uppercase tracking-wider text-brand-600 mb-3">Testimonios</div>
            <h2 class="text-display-sm font-display text-ink-900">
                Resultados reales,<br>de negocios reales.
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <?php
            $testimonials = [
                [
                    'quote' => 'Desde que instalamos el bot recuperamos 12 horas semanales de responder WhatsApps. Facturamos 30% más.',
                    'name' => 'María Gutiérrez',
                    'business' => 'Belleza Pura · Palermo',
                    'initials' => 'MG',
                    'color' => 'from-pink-400 to-rose-500',
                ],
                [
                    'quote' => 'Antes perdía 1 de cada 4 turnos por no-show. Ahora con los recordatorios automáticos, bajó a 1 de cada 20.',
                    'name' => 'Dr. Javier Romero',
                    'business' => 'Clínica Norte · Belgrano',
                    'initials' => 'JR',
                    'color' => 'from-blue-400 to-indigo-500',
                ],
                [
                    'quote' => 'La página pública nos trae 40% de los clientes nuevos. Los sábados se llena solo mientras entrenamos.',
                    'name' => 'Lucía Méndez',
                    'business' => 'Gym Palermo',
                    'initials' => 'LM',
                    'color' => 'from-emerald-400 to-teal-500',
                ],
            ];
            foreach ($testimonials as $i => $t): ?>
                <div class="rounded-2xl bg-white border border-ink-200/70 p-8 shadow-soft spot-card" data-reveal style="--reveal-delay: <?= ($i * 100) ?>ms">
                    <div class="flex gap-0.5 text-amber-400">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.1 6.3 7 1-5 4.9 1.2 6.9L12 17.8 5.7 21.1 7 14.2 2 9.3l7-1z"/></svg>
                        <?php endfor; ?>
                    </div>
                    <blockquote class="mt-4 text-ink-700 leading-relaxed">
                        "<?= e($t['quote']) ?>"
                    </blockquote>
                    <div class="mt-6 flex items-center gap-3 pt-6 border-t border-ink-100">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br <?= $t['color'] ?> flex items-center justify-center text-white text-sm font-bold">
                            <?= e($t['initials']) ?>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-ink-900"><?= e($t['name']) ?></div>
                            <div class="text-xs text-ink-500"><?= e($t['business']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     PRICING
     ============================================================ -->
<section id="pricing" class="py-24 bg-white" x-data="{ annual: false, fmt(n){ return 'AR$ ' + n.toLocaleString('es-AR'); } }">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-10" data-reveal>
            <div class="inline-block text-[11px] font-bold uppercase tracking-wider text-brand-600 mb-3">Precios</div>
            <h2 class="text-display-sm font-display text-ink-900">Un precio simple para cada negocio</h2>
            <p class="mt-4 text-ink-600 text-lg">Sin costos ocultos. Cancelá cuando quieras.</p>
        </div>

        <!-- Toggle Mensual / Anual -->
        <div class="flex items-center justify-center mb-12" data-reveal style="--reveal-delay: 80ms">
            <div class="toggle-pill" role="tablist" aria-label="Periodicidad de pago">
                <span class="toggle-thumb"
                      :style="annual ? 'left: 50%; width: calc(50% - 4px)' : 'left: 4px; width: calc(50% - 4px)'"></span>
                <button type="button" role="tab" :aria-selected="!annual" class="toggle-opt" :class="{ 'active': !annual }" @click="annual = false">Mensual</button>
                <button type="button" role="tab" :aria-selected="annual"  class="toggle-opt" :class="{ 'active': annual }"  @click="annual = true">Anual</button>
                <span class="save-pill save-pill-float">Ahorrá 17%</span>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <!-- STARTER -->
            <div class="relative rounded-2xl bg-white border border-ink-200 p-8 flex flex-col" data-reveal>
                <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Starter</div>
                <div class="mt-4 flex items-baseline gap-1">
                    <span class="text-4xl font-extrabold font-display text-ink-900">Gratis</span>
                </div>
                <p class="mt-2 text-sm text-ink-500">Para arrancar sin riesgo</p>
                <div class="my-6 border-t border-ink-100"></div>
                <ul class="space-y-3 text-sm text-ink-700 flex-1">
                    <?php foreach (['Hasta 50 turnos/mes','Panel de administración','Página pública de reservas','1 profesional','Soporte por email'] as $f): ?>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?= e($f) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="/register" class="mt-8 btn-press block text-center px-5 py-3 bg-ink-100 hover:bg-ink-200 text-ink-900 rounded-xl font-semibold transition">
                    Empezar gratis
                </a>
            </div>

            <!-- NEGOCIO (highlighted) -->
            <div class="relative rounded-2xl bg-gradient-to-b from-brand-600 to-brand-700 text-white p-8 flex flex-col shadow-brand scale-105 lg:scale-110" data-reveal style="--reveal-delay: 120ms">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-r from-amber-400 to-orange-400 text-ink-900 text-[10px] font-bold uppercase tracking-wider shadow-lg">
                        ⭐ Más elegido
                    </span>
                </div>
                <div class="text-xs font-bold uppercase tracking-wider text-brand-200">Negocio</div>
                <div class="mt-4 flex items-baseline gap-1">
                    <span class="text-4xl font-extrabold font-display tabular-nums" x-text="fmt(annual ? 20700 : 24900)">AR$ 24.900</span>
                    <span class="text-brand-200 text-sm">/mes</span>
                </div>
                <p class="mt-1 text-xs text-brand-100/90 h-4" x-text="annual ? 'Facturado anualmente · 2 meses gratis' : ''"></p>
                <p class="mt-2 text-sm text-brand-100">El plan para hacer crecer tu negocio</p>
                <div class="my-6 border-t border-white/20"></div>
                <ul class="space-y-3 text-sm flex-1">
                    <?php foreach (['Turnos ilimitados','Bot WhatsApp con IA (Claude)','Recordatorios automáticos','Hasta 5 profesionales','Pagos con MercadoPago','Analytics completo','Soporte prioritario'] as $f): ?>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-emerald-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?= e($f) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="/register" class="cta-shimmer mt-8 btn-press block text-center px-5 py-3 bg-white hover:bg-brand-50 text-brand-700 rounded-xl font-semibold transition shadow-lg">
                    Empezar 14 días gratis
                </a>
            </div>

            <!-- MULTI -->
            <div class="relative rounded-2xl bg-white border border-ink-200 p-8 flex flex-col" data-reveal style="--reveal-delay: 180ms">
                <div class="text-xs font-bold uppercase tracking-wider text-ink-500">Multi-Sucursal</div>
                <div class="mt-4 flex items-baseline gap-1">
                    <span class="text-4xl font-extrabold font-display text-ink-900 tabular-nums" x-text="fmt(annual ? 49700 : 59900)">AR$ 59.900</span>
                    <span class="text-ink-500 text-sm">/mes</span>
                </div>
                <p class="mt-1 text-xs text-ink-500 h-4" x-text="annual ? 'Facturado anualmente · 2 meses gratis' : ''"></p>
                <p class="mt-2 text-sm text-ink-500">Para cadenas y franquicias</p>
                <div class="my-6 border-t border-ink-100"></div>
                <ul class="space-y-3 text-sm text-ink-700 flex-1">
                    <?php foreach (['Todo lo de Negocio','Sucursales ilimitadas','Profesionales ilimitados','API REST','Integraciones custom','Manager de cuenta dedicado','SLA garantizado'] as $f): ?>
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <?= e($f) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="/register" class="mt-8 btn-press block text-center px-5 py-3 bg-ink-900 hover:bg-ink-800 text-white rounded-xl font-semibold transition">
                    Hablar con ventas
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FINAL CTA BAND
     ============================================================ -->
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700"></div>
    <div class="absolute inset-0 bg-grid-dark opacity-30"></div>
    <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] bg-accent-400/20 rounded-full blur-3xl"></div>

    <div class="relative max-w-4xl mx-auto px-6 text-center text-white">
        <h2 class="text-display-md font-display tracking-tight" data-reveal>
            Tu agenda llena,<br>sin levantar un dedo.
        </h2>
        <p class="mt-6 text-white/80 text-lg max-w-xl mx-auto" data-reveal style="--reveal-delay: 80ms">
            Empezá gratis en 3 minutos. Sin tarjeta, sin compromiso, sin complicaciones.
        </p>
        <div class="mt-10 flex flex-wrap justify-center gap-3" data-reveal style="--reveal-delay: 160ms">
            <a href="/register" class="cta-shimmer btn-press inline-flex items-center gap-2 px-8 py-4 bg-white text-brand-700 rounded-xl text-lg font-bold shadow-2xl hover:bg-brand-50">
                Empezar ahora
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
            <a href="#features" class="btn-press inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur border border-white/20 text-white rounded-xl text-lg font-bold hover:bg-white/20">
                Ver demo
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="bg-ink-950 text-white/70">
    <div class="max-w-7xl mx-auto px-6 py-16">
        <div class="grid md:grid-cols-5 gap-10">
            <!-- Brand column -->
            <div class="md:col-span-2">
                <?php $variant='light'; View::partial('partials/brand_logo'); ?>
                <p class="mt-4 text-sm max-w-sm leading-relaxed">
                    La plataforma de gestión de turnos con IA que tu negocio necesita.
                    Hecha con ♥ en Argentina.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <?php $socials = ['twitter','instagram','linkedin','youtube']; foreach ($socials as $s): ?>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center transition">
                            <span class="text-xs"><?= strtoupper(substr($s, 0, 2)) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            $cols = [
                'Producto' => [
                    ['Funcionalidades', '/#features'],
                    ['Precios', '/pricing'],
                    ['Bot WhatsApp', '/#features'],
                    ['Para quién', '/#industries'],
                ],
                'Empresa' => [
                    ['Sobre nosotros', '#'],
                    ['Clientes', '/#testimonials'],
                    ['Contacto', 'mailto:hola@turneroya.app'],
                ],
                'Legal' => [
                    ['Términos y Condiciones', '/terms'],
                    ['Política de Privacidad', '/privacy'],
                    ['Soporte', 'mailto:soporte@turneroya.app'],
                ],
            ];
            foreach ($cols as $col => $links): ?>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-white mb-4"><?= e($col) ?></div>
                    <ul class="space-y-3 text-sm">
                        <?php foreach ($links as [$label, $href]): ?>
                            <li><a href="<?= e($href) ?>" class="hover:text-white transition"><?= e($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-16 pt-8 border-t border-white/10 flex flex-wrap items-center justify-between gap-4 text-xs">
            <div>© <?= date('Y') ?> TurneroYa. Todos los derechos reservados.</div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Todos los sistemas operativos
                </span>
            </div>
        </div>
    </div>
</footer>

<?php View::endSection(); ?>
