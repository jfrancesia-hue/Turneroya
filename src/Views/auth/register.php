<?php use TurneroYa\Core\View; View::extend('layouts/auth'); ?>
<?php View::section('content'); ?>
<div>
    <h1 class="text-3xl font-extrabold text-ink-900">Activar mi agenda</h1>
    <p class="mt-2 text-sm text-ink-500">
        Ya tenes cuenta?
        <a href="/login" class="font-semibold text-brand-600 hover:text-brand-700">Ingresar</a>
    </p>
</div>

<div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-brand-50 border border-brand-100 text-brand-700 text-xs font-semibold">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    14 dias gratis. Sin tarjeta.
</div>

<form method="POST" action="/register" class="mt-8 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Tu nombre</label>
        <input type="text" name="name" value="<?= e(old('name')) ?>" required minlength="2" autofocus
               placeholder="Como te llamas?"
               class="focus-ring w-full px-4 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Email</label>
        <input type="email" name="email" value="<?= e(old('email')) ?>" required
               placeholder="tu@email.com"
               class="focus-ring w-full px-4 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Contrasena</label>
        <input type="password" name="password" required minlength="6"
               placeholder="Minimo 6 caracteres"
               class="focus-ring w-full px-4 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Repetir contrasena</label>
        <input type="password" name="password_confirmation" required minlength="6"
               placeholder="Confirmar clave"
               class="focus-ring w-full px-4 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <button type="submit" class="btn-press w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-semibold shadow-brand transition">
        Crear cuenta gratis
    </button>
</form>

<p class="mt-8 text-center text-xs text-ink-400">
    Al crear tu cuenta aceptas nuestros <a href="/terms" class="text-ink-600 hover:text-ink-900 underline">Terminos</a> y <a href="/privacy" class="text-ink-600 hover:text-ink-900 underline">Privacidad</a>.
</p>
<?php View::endSection(); ?>
