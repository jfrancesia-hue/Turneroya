<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl">
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-2xl p-5 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            <div>
                <div class="text-lg font-bold">Bot de WhatsApp con IA</div>
                <div class="text-sm opacity-90">Powered by Claude (Anthropic)</div>
            </div>
        </div>
    </div>

    <form method="POST" action="/dashboard/bot/config" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <?= csrf_field() ?>

        <label class="flex items-center gap-3 p-3 rounded-xl border-2 border-slate-200 cursor-pointer">
            <input type="checkbox" name="bot_enabled" value="1" <?= !empty($business['bot_enabled']) ? 'checked' : '' ?> class="w-5 h-5">
            <div>
                <div class="font-medium">Bot activo</div>
                <div class="text-xs text-slate-500">Cuando esté activo, responderá automáticamente los mensajes de WhatsApp</div>
            </div>
        </label>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Número de WhatsApp del negocio</label>
            <input type="tel" name="whatsapp" value="<?= e($business['whatsapp'] ?? '') ?>" placeholder="+5491122334455" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            <p class="text-xs text-slate-500 mt-1">Debe coincidir con el número configurado en Twilio.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Personalidad del bot</label>
            <input type="text" name="bot_personality" value="<?= e($business['bot_personality'] ?? 'profesional y amigable') ?>" class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            <p class="text-xs text-slate-500 mt-1">Ej: "profesional y amigable", "joven y divertido", "serio y formal"</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mensaje de bienvenida (opcional)</label>
            <textarea name="bot_welcome_message" rows="3" placeholder="Info adicional para el bot. Ej: 'Estamos en Av. Corrientes 1234. Aceptamos efectivo y MercadoPago.'" class="w-full px-4 py-2.5 rounded-lg border border-slate-300"><?= e($business['bot_welcome_message'] ?? '') ?></textarea>
        </div>

        <div class="pt-4 border-t border-slate-200">
            <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Guardar configuración</button>
        </div>
    </form>

    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-2xl p-5 text-sm text-blue-900">
        <div class="font-semibold mb-2">📋 Cómo configurar el webhook en Twilio</div>
        <ol class="list-decimal list-inside space-y-1 text-blue-800">
            <li>Entrá a <a href="https://console.twilio.com" target="_blank" class="underline">Twilio Console</a></li>
            <li>Messaging → Senders → WhatsApp senders</li>
            <li>Configurá el webhook de mensajes entrantes apuntando a:<br>
                <code class="bg-blue-100 px-2 py-0.5 rounded mt-1 inline-block"><?= e(url('/api/webhook/whatsapp')) ?></code>
            </li>
            <li>Método: POST</li>
        </ol>
    </div>
</div>
<?php View::endSection(); ?>
