<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<nav class="site-nav is-scrolled">
    <div class="site-nav-inner">
        <?php View::partial('partials/brand_logo'); ?>
        <div class="site-nav-actions">
            <a href="/" class="nav-login">Volver al inicio</a>
            <a href="/register" class="nav-primary">Empezar gratis</a>
        </div>
    </div>
</nav>

<main class="pricing-page" x-data="{cycle:'MONTHLY'}">
    <section class="pricing-hero">
        <span>Precios</span>
        <h1>Planes claros para llenar la agenda sin sumar trabajo.</h1>
        <p>Prob&aacute; Reservia gratis. Cuando est&eacute;s listo, eleg&iacute; el plan que acompa&ntilde;e el ritmo de tu negocio.</p>
        <div class="billing-toggle" role="tablist">
            <button type="button" :class="cycle==='MONTHLY' ? 'active' : ''" @click="cycle='MONTHLY'">Mensual</button>
            <button type="button" :class="cycle==='YEARLY' ? 'active' : ''" @click="cycle='YEARLY'">Anual -17%</button>
        </div>
    </section>

    <section class="pricing-page-grid">
        <?php foreach ($plans as $plan):
            $isHighlighted = (bool) $plan['is_featured'];
            $monthly = (float) $plan['price_monthly'];
            $yearly = (float) ($plan['price_yearly'] ?? 0);
            $yearlyMonthly = $yearly > 0 ? $yearly / 12 : $monthly;
            $isFree = $monthly == 0.0;
        ?>
            <article class="price-card <?= $isHighlighted ? 'featured' : '' ?>">
                <?php if ($isHighlighted): ?>
                    <div class="badge">Mas elegido</div>
                <?php endif; ?>

                <span><?= e($plan['name']) ?></span>
                <?php if ($isFree): ?>
                    <h3>Gratis</h3>
                <?php else: ?>
                    <h3>
                        <span x-show="cycle==='MONTHLY'">AR$ <?= number_format($monthly, 0, ',', '.') ?></span>
                        <span x-show="cycle==='YEARLY'" x-cloak>AR$ <?= number_format($yearlyMonthly, 0, ',', '.') ?></span>
                        <small>/mes</small>
                    </h3>
                <?php endif; ?>

                <p><?= e($plan['tagline']) ?></p>

                <ul>
                    <?php foreach (($plan['features'] ?? []) as $feature): ?>
                        <li><?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($plan['id'] === 'FREE'): ?>
                    <a href="/register">Empezar gratis</a>
                <?php else: ?>
                    <a href="/register?plan=<?= e($plan['id']) ?>">
                        <?= $isHighlighted ? 'Probar 14 dias gratis' : 'Elegir ' . e($plan['name']) ?>
                    </a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="pricing-faq">
        <div class="section-heading">
            <span>Preguntas frecuentes</span>
            <h2>Todo lo que necesit&aacute;s saber antes de activar tu agenda.</h2>
        </div>

        <div class="faq-list" x-data="{open:0}">
            <?php
            $faqs = [
                ['&iquest;Tengo que poner tarjeta para probar?', 'No. Pod&eacute;s probar el producto gratis y decidir despu&eacute;s si quer&eacute;s pasar a un plan pago.'],
                ['&iquest;El bot usa mi WhatsApp?', 'S&iacute;. Se conecta con tu n&uacute;mero de WhatsApp Business mediante Twilio. Te guiamos en el setup.'],
                ['&iquest;Puedo cobrar se&ntilde;as?', 'S&iacute;. MercadoPago est&aacute; integrado para cobrar se&ntilde;as y liberar turnos cuando un pago vence.'],
                ['&iquest;Puedo cancelar cuando quiera?', 'S&iacute;. No hay permanencia. Pod&eacute;s cancelar o cambiar de plan desde el dashboard.'],
                ['&iquest;Sirve para varios profesionales?', 'S&iacute;. Pod&eacute;s manejar profesionales, servicios, horarios, bloqueos y recordatorios desde un solo panel.'],
            ];
            foreach ($faqs as $i => [$question, $answer]): ?>
                <article>
                    <button type="button" @click="open = open === <?= $i ?> ? -1 : <?= $i ?>">
                        <span><?= $question ?></span>
                        <strong x-text="open === <?= $i ?> ? '-' : '+'"></strong>
                    </button>
                    <p x-show="open === <?= $i ?>" x-cloak><?= $answer ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php View::endSection(); ?>
