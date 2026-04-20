<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-ink-200/60">
    <div class="max-w-7xl mx-auto px-6 h-[72px] flex items-center justify-between">
        <?php View::partial('partials/brand_logo'); ?>
        <a href="/" class="text-sm font-semibold text-ink-700 hover:text-ink-900 px-3 py-2">← Volver al inicio</a>
    </div>
</nav>

<article class="pt-[120px] pb-24 max-w-3xl mx-auto px-6 prose prose-slate">
    <h1 class="text-display-sm text-ink-900">Política de Privacidad</h1>
    <p class="text-sm text-ink-500">Última actualización: <?= date('d/m/Y') ?></p>

    <p>Esta política describe cómo TurneroYa recopila, usa y protege tus datos personales y los de tus clientes finales, en cumplimiento de la Ley 25.326 de Protección de Datos Personales de la República Argentina.</p>

    <h2>1. Datos que recopilamos</h2>
    <ul>
        <li><strong>Datos de cuenta:</strong> nombre, email, contraseña (cifrada), datos del negocio, número de WhatsApp.</li>
        <li><strong>Datos de clientes finales:</strong> nombre, teléfono, email, historial de turnos. Los ingresa el titular de la cuenta o los clientes al reservar.</li>
        <li><strong>Datos de pago:</strong> procesados íntegramente por MercadoPago. No almacenamos datos de tarjetas.</li>
        <li><strong>Datos de uso:</strong> logs de acceso, IP, user agent, para seguridad y mejora del servicio.</li>
    </ul>

    <h2>2. Finalidades</h2>
    <ul>
        <li>Prestar el servicio contratado (gestión de turnos, recordatorios, bot WhatsApp).</li>
        <li>Cobrar la suscripción vía MercadoPago.</li>
        <li>Enviar comunicaciones operativas (confirmaciones, recordatorios, cambios de plan).</li>
        <li>Cumplir obligaciones legales (facturación AFIP, requerimientos judiciales).</li>
    </ul>

    <h2>3. Base legal</h2>
    <p>Tratamos los datos en base al contrato de servicio (art. 5° Ley 25.326), al consentimiento del titular y al interés legítimo para operar la plataforma.</p>

    <h2>4. Compartimos datos con:</h2>
    <ul>
        <li><strong>Anthropic</strong> (Claude AI) — procesa mensajes del bot WhatsApp para generar respuestas. No se usan para entrenar modelos.</li>
        <li><strong>Twilio</strong> — envío y recepción de mensajes WhatsApp.</li>
        <li><strong>MercadoPago</strong> — procesamiento de pagos.</li>
        <li><strong>Proveedores de hosting e infraestructura.</strong></li>
    </ul>
    <p>No vendemos datos a terceros.</p>

    <h2>5. Plazo de conservación</h2>
    <p>Conservamos los datos mientras la cuenta esté activa y hasta 5 años después de su baja, por obligaciones fiscales y contables. Podés solicitar la eliminación anticipada escribiendo a <a href="mailto:privacy@turneroya.app">privacy@turneroya.app</a> (salvo datos que debamos conservar por ley).</p>

    <h2>6. Tus derechos (Ley 25.326)</h2>
    <p>Tenés derecho a acceder, rectificar, actualizar y suprimir tus datos personales, y a retirar tu consentimiento en cualquier momento. Para ejercerlos: <a href="mailto:privacy@turneroya.app">privacy@turneroya.app</a></p>
    <p>La Agencia de Acceso a la Información Pública es el órgano de control (<a href="https://www.argentina.gob.ar/aaip" target="_blank" rel="noopener">www.argentina.gob.ar/aaip</a>).</p>

    <h2>7. Seguridad</h2>
    <p>Aplicamos medidas técnicas y organizativas razonables: cifrado TLS en tránsito, contraseñas hasheadas con bcrypt, backups periódicos, controles de acceso y auditoría. Ningún sistema es 100% seguro; notificamos incidentes que afecten tus datos dentro de las 72 horas de conocidos.</p>

    <h2>8. Cookies</h2>
    <p>Usamos cookies estrictamente necesarias para la sesión y preferencias. No utilizamos cookies de publicidad de terceros.</p>

    <h2>9. Menores de edad</h2>
    <p>El servicio no está dirigido a menores de 18 años. Si detectamos datos de menores sin consentimiento parental, los eliminamos.</p>

    <h2>10. Contacto</h2>
    <p><strong>Responsable:</strong> TurneroYa<br>
    <strong>Email:</strong> <a href="mailto:privacy@turneroya.app">privacy@turneroya.app</a></p>
</article>

<?php View::endSection(); ?>
