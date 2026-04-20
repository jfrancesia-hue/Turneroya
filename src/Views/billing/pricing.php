<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-ink-200/60">
    <div class="max-w-7xl mx-auto px-6 h-[72px] flex items-center justify-between">
        <?php View::partial('partials/brand_logo'); ?>
        <div class="flex items-center gap-2">
            <a href="/" class="text-sm font-semibold text-ink-700 hover:text-ink-900 px-3 py-2">← Volver al inicio</a>
            <a href="/register" class="btn-press px-4 py-2.5 bg-ink-900 text-white rounded-xl text-sm font-semibold hover:bg-ink-800 shadow-soft">
                Empezar gratis
            </a>
        </div>
    </div>
</nav>

<section class="pt-[120px] pb-16" x-data="{cycle:'MONTHLY'}">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-12">
            <div class="inline-block text-[11px] font-bold uppercase tracking-wider text-brand-600 mb-3">Precios</div>
            <h1 class="text-display-md text-ink-900">Planes para cada etapa del negocio</h1>
            <p class="mt-4 text-ink-600 text-lg max-w-xl mx-auto">
                Pagá mensual o anual (2 meses gratis). Cancelá cuando quieras.
            </p>

            <!-- Billing cycle toggle -->
            <div class="mt-8 inline-flex items-center gap-1 p-1 rounded-xl bg-ink-100 border border-ink-200">
                <button @click="cycle='MONTHLY'" :class="cycle==='MONTHLY' ? 'bg-white shadow-soft text-ink-900' : 'text-ink-500'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Mensual
                </button>
                <button @click="cycle='YEARLY'" :class="cycle==='YEARLY' ? 'bg-white shadow-soft text-ink-900' : 'text-ink-500'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Anual
                    <span class="ml-1 inline-block px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[10px] font-bold">-17%</span>
                </button>
            </div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5 max-w-6xl mx-auto">
            <?php foreach ($plans as $plan):
                $isHighlighted = $plan['is_featured'];
                $monthly = (float) $plan['price_monthly'];
                $yearly = (float) ($plan['price_yearly'] ?? 0);
                $yearlyMonthly = $yearly > 0 ? $yearly / 12 : $monthly;
                ?>
                <div class="relative rounded-2xl <?= $isHighlighted ? 'bg-gradient-to-b from-brand-600 to-brand-700 text-white shadow-brand scale-[1.02]' : 'bg-white border border-ink-200' ?> p-6 flex flex-col">
                    <?php if ($isHighlighted): ?>
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-r from-amber-400 to-orange-400 text-ink-900 text-[10px] font-bold uppercase tracking-wider shadow-lg">
                                ⭐ Más elegido
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="text-xs font-bold uppercase tracking-wider <?= $isHighlighted ? 'text-brand-200' : 'text-ink-500' ?>">
                        <?= e($plan['name']) ?>
                    </div>

                    <div class="mt-4 flex items-baseline gap-1">
                        <?php if ($monthly == 0): ?>
                            <span class="text-4xl font-extrabold"><?= $isHighlighted ? '' : '' ?>Gratis</span>
                        <?php else: ?>
                            <span class="text-4xl font-extrabold" x-show="cycle==='MONTHLY'">
                                AR$ <?= number_format($monthly, 0, ',', '.') ?>
                            </span>
                            <span class="text-4xl font-extrabold" x-show="cycle==='YEARLY'" x-cloak>
                                AR$ <?= number_format($yearlyMonthly, 0, ',', '.') ?>
                            </span>
                            <span class="<?= $isHighlighted ? 'text-brand-200' : 'text-ink-500' ?> text-sm">/mes</span>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 text-sm <?= $isHighlighted ? 'text-brand-100' : 'text-ink-500' ?>">
                        <?= e($plan['tagline']) ?>
                    </p>

                    <div class="my-6 border-t <?= $isHighlighted ? 'border-white/20' : 'border-ink-100' ?>"></div>

                    <ul class="space-y-2.5 text-sm flex-1 <?= $isHighlighted ? '' : 'text-ink-700' ?>">
                        <?php foreach (($plan['features'] ?? []) as $f): ?>
                            <li class="flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 <?= $isHighlighted ? 'text-emerald-300' : 'text-emerald-500' ?>" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <?= e($f) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($plan['id'] === 'FREE'): ?>
                        <a href="/register" class="mt-6 btn-press block text-center px-5 py-3 bg-ink-100 hover:bg-ink-200 text-ink-900 rounded-xl font-semibold transition">
                            Empezar gratis
                        </a>
                    <?php elseif ($isHighlighted): ?>
                        <a href="/register?plan=<?= e($plan['id']) ?>" class="mt-6 btn-press block text-center px-5 py-3 bg-white hover:bg-brand-50 text-brand-700 rounded-xl font-semibold transition shadow-lg">
                            Empezar 14 días gratis
                        </a>
                    <?php else: ?>
                        <a href="/register?plan=<?= e($plan['id']) ?>" class="mt-6 btn-press block text-center px-5 py-3 bg-ink-900 hover:bg-ink-800 text-white rounded-xl font-semibold transition">
                            Elegir <?= e($plan['name']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Preguntas frecuentes -->
        <div class="mt-24 max-w-3xl mx-auto">
            <h2 class="text-2xl font-bold text-ink-900 text-center mb-8">Preguntas frecuentes</h2>
            <div class="space-y-3" x-data="{open:-1}">
                <?php
                $faqs = [
                    ['¿Cómo funciona el período de prueba gratis?', 'Tenés 14 días para probar todas las funcionalidades del plan que elijas, sin tarjeta de crédito. Al finalizar, pasás al plan Free o elegís un plan pago.'],
                    ['¿Puedo cambiar de plan en cualquier momento?', 'Sí. Podés subir o bajar de plan cuando quieras. Si subís, se prorratea el cobro. Si bajás, el cambio aplica al próximo ciclo.'],
                    ['¿Qué métodos de pago aceptan?', 'Aceptamos todos los métodos disponibles en MercadoPago: tarjetas de crédito/débito, efectivo (Rapipago/Pago Fácil) y transferencia bancaria.'],
                    ['¿Emiten factura fiscal?', 'Sí. Emitimos Factura A o B según corresponda a través de AFIP. La recibís automáticamente por email después de cada cobro.'],
                    ['¿Puedo cancelar cuando quiera?', 'Sí. Cancelás desde el dashboard con un click y no te cobramos más. Usás el servicio hasta el fin del período pagado.'],
                    ['¿El bot de WhatsApp usa mi número?', 'Sí. Conectás tu número de WhatsApp Business a través de Twilio. Nosotros te guiamos en el setup (toma 10 minutos).'],
                ];
                foreach ($faqs as $i => [$q, $a]): ?>
                    <div class="rounded-xl bg-white border border-ink-200/70 overflow-hidden">
                        <button @click="open = open === <?= $i ?> ? -1 : <?= $i ?>" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-ink-50/50 transition">
                            <span class="font-semibold text-ink-900"><?= e($q) ?></span>
                            <svg :class="open === <?= $i ?> ? 'rotate-180' : ''" class="w-5 h-5 text-ink-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === <?= $i ?>" x-cloak class="px-6 pb-5 text-ink-600 text-sm leading-relaxed">
                            <?= e($a) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php View::endSection(); ?>
