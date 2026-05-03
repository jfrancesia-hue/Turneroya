<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-ink-200/60">
    <div class="max-w-7xl mx-auto px-6 h-[72px] flex items-center justify-between">
        <?php View::partial('partials/brand_logo'); ?>
        <a href="/" class="text-sm font-semibold text-ink-700 hover:text-ink-900 px-3 py-2">← Volver al inicio</a>
    </div>
</nav>

<article class="pt-[120px] pb-24 max-w-3xl mx-auto px-6 prose prose-slate">
    <h1 class="text-display-sm text-ink-900">Términos y Condiciones</h1>
    <p class="text-sm text-ink-500">Última actualización: <?= date('d/m/Y') ?></p>

    <h2>1. Aceptación</h2>
    <p>El uso de Reservia (la "Plataforma"), operada desde la República Argentina, implica la aceptación de los siguientes términos. Si no estás de acuerdo, no utilices el servicio.</p>

    <h2>2. Servicio</h2>
    <p>Reservia ofrece una plataforma SaaS para gestión de turnos, calendario, comunicación por WhatsApp (mediante integración con Twilio), cobros (MercadoPago) y analytics. El servicio se presta "como está" y puede modificarse con aviso previo.</p>

    <h2>3. Cuentas</h2>
    <p>El usuario es responsable de mantener la confidencialidad de sus credenciales, de la veracidad de los datos registrados y de toda actividad realizada bajo su cuenta.</p>

    <h2>4. Planes y pagos</h2>
    <p>Los planes se cobran mensual o anualmente mediante MercadoPago. El período de prueba gratis de 14 días no requiere medio de pago. Al contratar un plan pago, se autoriza el débito automático recurrente. El usuario puede cancelar en cualquier momento desde el panel, y el servicio continuará hasta el fin del período pagado. No se realizan reembolsos por períodos parciales ya consumidos.</p>

    <h2>5. Facturación</h2>
    <p>Reservia emite factura fiscal conforme a la normativa de AFIP (Argentina) al correo de facturación registrado por el cliente.</p>

    <h2>6. Uso aceptable</h2>
    <p>Queda prohibido utilizar la plataforma para actividades ilegales, envío de spam, suplantación de identidad o violación de derechos de terceros. Nos reservamos el derecho de suspender cuentas que incumplan estas condiciones.</p>

    <h2>7. Datos de clientes finales</h2>
    <p>El titular de la cuenta (negocio) es el responsable del tratamiento de los datos de sus clientes finales. Reservia actúa como encargado del tratamiento, procesando esos datos únicamente para prestar el servicio contratado, en los términos de la Ley 25.326 de Protección de Datos Personales.</p>

    <h2>8. Disponibilidad</h2>
    <p>Nos esforzamos por mantener el servicio disponible 24/7. Sin embargo, no garantizamos un uptime específico salvo que se contrate un plan con SLA explícito.</p>

    <h2>9. Limitación de responsabilidad</h2>
    <p>Reservia no será responsable por daños indirectos, lucro cesante, pérdida de datos o interrupciones ajenas a nuestro control (proveedores de WhatsApp, MercadoPago, hosting). La responsabilidad máxima acumulada no excederá el monto pagado por el cliente en los últimos 3 meses.</p>

    <h2>10. Modificaciones</h2>
    <p>Podemos modificar estos términos con aviso previo de 30 días vía email. El uso continuado del servicio implica aceptación de los nuevos términos.</p>

    <h2>11. Ley aplicable</h2>
    <p>Estos términos se rigen por las leyes de la República Argentina. Cualquier controversia será sometida a los tribunales ordinarios de la Ciudad Autónoma de Buenos Aires.</p>

    <h2>12. Contacto</h2>
    <p>Para consultas: <a href="mailto:legal@reservia.app">legal@reservia.app</a></p>
</article>

<?php View::endSection(); ?>
