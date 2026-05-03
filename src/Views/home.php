<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<nav x-data="{open:false, scrolled:false}"
     @scroll.window="scrolled = window.scrollY > 12"
     :class="scrolled ? 'site-nav is-scrolled' : 'site-nav'"
     class="site-nav">
    <div class="site-nav-inner">
        <?php View::partial('partials/brand_logo'); ?>

        <div class="site-nav-links">
            <a href="#producto">Producto</a>
            <a href="#resultados">Resultados</a>
            <a href="#industrias">Rubros</a>
            <a href="#pricing">Precios</a>
        </div>

        <div class="site-nav-actions">
            <a href="/login" class="nav-login">Ingresar</a>
            <a href="/register" class="nav-primary">Empezar gratis</a>
            <button type="button" class="nav-menu" @click="open = !open" aria-label="Abrir menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="mobile-panel" x-show="open" x-cloak x-transition.opacity>
        <a href="#producto" @click="open=false">Producto</a>
        <a href="#resultados" @click="open=false">Resultados</a>
        <a href="#industrias" @click="open=false">Rubros</a>
        <a href="#pricing" @click="open=false">Precios</a>
        <a href="/login" @click="open=false">Ingresar</a>
        <a href="/register" @click="open=false" class="mobile-primary">Empezar gratis</a>
    </div>
</nav>

<main>
    <section class="hero-stage">
        <div class="hero-bg-ui" aria-hidden="true">
            <div class="phone-preview">
                <div class="phone-top">
                    <span></span>
                    <strong>Reservia Bot</strong>
                    <em>online</em>
                </div>
                <div class="chat-row bot">Hola, soy el asistente de Estudio Aura. &iquest;Que turno necesit&aacute;s?</div>
                <div class="chat-row user">Corte y brushing el viernes despu&eacute;s de las 16</div>
                <div class="chat-row bot">Tengo 16:30 con Sof&iacute;a. Te reservo y te paso la se&ntilde;a por MercadoPago.</div>
                <div class="chat-actions">
                    <span>Confirmar 16:30</span>
                    <span>Ver otros horarios</span>
                </div>
            </div>

            <div class="calendar-preview">
                <div class="calendar-head">
                    <strong>Agenda de hoy</strong>
                    <span>38 turnos</span>
                </div>
                <div class="calendar-grid">
                    <?php foreach (['09','10','11','12','14','15','16','17'] as $hour): ?>
                        <div class="calendar-time"><?= e($hour) ?>:00</div>
                        <div class="calendar-slot <?= in_array($hour, ['10','15','16'], true) ? 'busy' : '' ?>">
                            <?= in_array($hour, ['10','15','16'], true) ? 'Reservado' : 'Disponible' ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="payment-preview">
                <span class="payment-icon">$</span>
                <div>
                    <strong>Se&ntilde;a cobrada</strong>
                    <span>MercadoPago aprobado</span>
                </div>
            </div>
        </div>

        <div class="hero-content">
            <div class="hero-kicker">
                <span></span>
                Identidad Reservia: WhatsApp que atiende, agenda que se llena
            </div>
            <h1>Tu WhatsApp atiende. Tu agenda se llena.</h1>
            <p>
                Convertimos mensajes en turnos confirmados: IA para responder, calendario para ordenar,
                MercadoPago para asegurar se&ntilde;as y recordatorios para que el cliente llegue.
            </p>

            <div class="hero-actions">
                <a href="/register" class="btn-main">
                    Empezar 14 d&iacute;as gratis
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
                <a href="#producto" class="btn-ghost">Ver producto</a>
            </div>

            <div class="hero-proof" id="resultados">
                <div><strong>82%</strong><span>menos plantones</span></div>
                <div><strong>24/7</strong><span>recepcionista digital</span></div>
                <div><strong>1 turno</strong><span>recuperado ya lo paga</span></div>
            </div>
        </div>
    </section>

    <section class="logo-strip" aria-label="Negocios que usan Reservia">
        <span>WhatsApp Business</span>
        <span>MercadoPago</span>
        <span>Turnos online</span>
        <span>Recordatorios</span>
        <span>Agenda inteligente</span>
        <span>IA en espa&ntilde;ol</span>
    </section>

    <section id="producto" class="section-pad">
        <div class="section-heading">
            <span>Producto</span>
            <h2>Una m&aacute;quina simple para transformar consultas en reservas.</h2>
            <p>El cliente escribe como siempre. Reservia entiende, propone horarios reales, cobra si hace falta y deja todo guardado.</p>
        </div>

        <div class="feature-layout">
            <article class="feature-large">
                <div class="feature-media">
                    <div class="mini-dashboard">
                        <div class="mini-toolbar">
                            <span></span><span></span><span></span>
                            <strong>Panel en vivo</strong>
                        </div>
                        <div class="mini-stats">
                            <div><strong>38</strong><span>turnos hoy</span></div>
                            <div><strong>$186k</strong><span>cobrado</span></div>
                            <div><strong>94%</strong><span>asistencia</span></div>
                        </div>
                        <div class="mini-list">
                            <div><span class="dot emerald"></span>Corte &middot; Maria <em>15:00</em></div>
                            <div><span class="dot amber"></span>Consulta &middot; Javier <em>15:30</em></div>
                            <div><span class="dot rose"></span>Manicura &middot; Ana <em>16:00</em></div>
                        </div>
                    </div>
                </div>
                <div class="feature-copy">
                    <span class="eyebrow">TurnoFlow</span>
                    <h3>Un flujo propio: mensaje, horario, se&ntilde;a, confirmaci&oacute;n.</h3>
                    <p>Gestion&aacute; profesionales, servicios, horarios, bloqueos, se&ntilde;as y recordatorios desde un panel pensado para trabajar r&aacute;pido.</p>
                </div>
            </article>

            <?php
            $features = [
                ['Recepcionista IA', 'Responde consultas, detecta intencion y reserva turnos con lenguaje natural.', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-5l-5 5v-5z'],
                ['Anti-plantones', 'Confirmaciones y recordatorios por WhatsApp antes del turno.', 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9'],
                ['Seña segura', 'Cobr&aacute; con MercadoPago y liber&aacute; el horario si el pago vence.', 'M17 9V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m2 4h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm7-5a2 2 0 1 1-4 0 2 2 0 0 1 4 0z'],
                ['Link que vende', 'Compartilo en Instagram, Google Maps o tu web y recib&iacute; reservas sin fricci&oacute;n.', 'M13.8 10.2 21 3m0 0h-5.5M21 3v5.5M3 21l7.2-7.2M3 21h5.5M3 21v-5.5'],
            ];
            foreach ($features as [$featureTitle, $desc, $icon]): ?>
                <article class="feature-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= e($icon) ?>"/></svg>
                    <h3><?= e($featureTitle) ?></h3>
                    <p><?= $desc ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="conversion-band">
        <div>
            <span>Promesa Reservia</span>
            <h2>Menos caos en el celular. M&aacute;s gente sentada en tu negocio.</h2>
        </div>
        <a href="/register">Quiero probarlo</a>
    </section>

    <section id="industrias" class="section-pad industries-section">
        <div class="section-heading">
            <span>Rubros</span>
            <h2>Hecho para negocios donde cada hueco libre cuesta plata.</h2>
        </div>
        <div class="industry-grid">
            <?php foreach ([
                ['Peluquerias', 'Cortes, color, brushing y estetica'],
                ['Consultorios', 'Medicos, odontologos y terapeutas'],
                ['Gimnasios', 'Clases, evaluaciones y turnos premium'],
                ['Veterinarias', 'Consultas, vacunas y peluqueria canina'],
                ['Talleres', 'Recepcion, diagnostico y entregas'],
                ['Estudios', 'Fotos, tatuajes, abogados y contadores'],
            ] as [$name, $copy]): ?>
                <article>
                    <strong><?= e($name) ?></strong>
                    <span><?= e($copy) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="testimonial-section">
        <div class="section-heading">
            <span>Confianza</span>
            <h2>Tu cliente no espera. Tu negocio tampoco deber&iacute;a.</h2>
        </div>
        <div class="testimonial-grid">
            <?php foreach ([
                ['MG', 'Maria Gutierrez', 'Belleza Pura', 'Pasamos de responder mensajes toda la noche a despertarnos con la agenda ya organizada.'],
                ['JR', 'Javier Romero', 'Clinica Norte', 'Los recordatorios bajaron muchisimo las ausencias. El equipo recupero tiempo real.'],
                ['LM', 'Lucia Mendez', 'Gym Palermo', 'El link publico nos trae reservas desde Instagram sin tener a alguien pendiente del telefono.'],
            ] as [$initials, $name, $business, $quote]): ?>
                <article>
                    <div class="stars">★★★★★</div>
                    <p>"<?= e($quote) ?>"</p>
                    <div class="person">
                        <span><?= e($initials) ?></span>
                        <div><strong><?= e($name) ?></strong><em><?= e($business) ?></em></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="pricing" class="section-pad pricing-section" x-data="{annual:false}">
        <div class="section-heading">
            <span>Precios</span>
            <h2>Empez&aacute; sin riesgo y escal&aacute; cuando el negocio lo pida.</h2>
            <div class="billing-toggle" role="tablist">
                <button type="button" :class="!annual ? 'active' : ''" @click="annual=false">Mensual</button>
                <button type="button" :class="annual ? 'active' : ''" @click="annual=true">Anual -17%</button>
            </div>
        </div>

        <div class="pricing-grid">
            <article class="price-card">
                <span>Starter</span>
                <h3>Gratis</h3>
                <p>Para probar la plataforma con tu primer profesional.</p>
                <ul>
                    <li>50 turnos por mes</li>
                    <li>Pagina publica de reservas</li>
                    <li>Panel de administracion</li>
                    <li>Soporte por email</li>
                </ul>
                <a href="/register">Empezar gratis</a>
            </article>

            <article class="price-card featured">
                <div class="badge">Mas elegido</div>
                <span>Negocio</span>
                <h3><span x-text="annual ? 'AR$ 20.700' : 'AR$ 24.900'">AR$ 24.900</span><small>/mes</small></h3>
                <p>Para negocios que quieren automatizar WhatsApp y cobrar se&ntilde;as.</p>
                <ul>
                    <li>Turnos ilimitados</li>
                    <li>Bot WhatsApp con IA</li>
                    <li>Recordatorios automaticos</li>
                    <li>Pagos con MercadoPago</li>
                    <li>Analytics completo</li>
                </ul>
                <a href="/register">Probar 14 dias gratis</a>
            </article>

            <article class="price-card">
                <span>Multi-Sucursal</span>
                <h3><span x-text="annual ? 'AR$ 49.700' : 'AR$ 59.900'">AR$ 59.900</span><small>/mes</small></h3>
                <p>Para equipos con varias sedes, profesionales y necesidades a medida.</p>
                <ul>
                    <li>Todo lo de Negocio</li>
                    <li>Sucursales multiples</li>
                    <li>Profesionales ilimitados</li>
                    <li>Integraciones custom</li>
                </ul>
                <a href="/register?plan=MULTI_SUCURSAL">Hablar con ventas</a>
            </article>
        </div>
    </section>

    <section class="final-cta">
        <h2>Tu pr&oacute;ximo cliente ya est&aacute; escribiendo por WhatsApp.</h2>
        <p>Dej&aacute; que Reservia lo atienda, le cobre la se&ntilde;a y lo convierta en un turno confirmado.</p>
        <a href="/register">Activar mi agenda gratis</a>
    </section>
</main>

<footer class="site-footer">
    <div>
        <?php View::partial('partials/brand_logo', ['variant' => 'light']); ?>
        <p>Turnos, pagos y WhatsApp con IA para negocios de servicios.</p>
    </div>
    <nav>
        <a href="/pricing">Precios</a>
        <a href="/terms">Terminos</a>
        <a href="/privacy">Privacidad</a>
        <a href="mailto:hola@reservia.app">Contacto</a>
    </nav>
</footer>

<?php View::endSection(); ?>
