<?php use TurneroYa\Core\View; View::extend('layouts/auth'); ?>
<?php View::section('content'); ?>
<div>
    <h1 class="text-3xl font-extrabold tracking-tight text-ink-900">Crear tu cuenta</h1>
    <p class="mt-2 text-sm text-ink-500">
        ¿Ya tenés cuenta?
        <a href="/login" class="font-semibold text-brand-600 hover:text-brand-700">Iniciar sesión</a>
    </p>
</div>

<!-- Badge: 14 días gratis -->
<div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    14 días gratis · sin tarjeta
</div>

<form method="POST" action="/register" class="mt-8 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Tu nombre</label>
        <input type="text" name="name" value="<?= e(old('name')) ?>" required minlength="2" autofocus
               placeholder="¿Cómo te llamás?"
               class="focus-ring w-full px-4 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Email</label>
        <input type="email" name="email" value="<?= e(old('email')) ?>" required
               placeholder="tu@email.com"
               class="focus-ring w-full px-4 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Contraseña</label>
        <input type="password" name="password" required minlength="6"
               placeholder="Mínimo 6 caracteres"
               class="focus-ring w-full px-4 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Repetir contraseña</label>
        <input type="password" name="password_confirmation" required minlength="6"
               placeholder="Confirmá tu contraseña"
               class="focus-ring w-full px-4 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
    </div>

    <button type="submit" class="btn-press w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-semibold shadow-brand transition">
        Crear mi cuenta gratis →
    </button>
</form>

<div class="mt-8 pt-8 border-t border-ink-100">
    <div class="text-xs text-ink-400 text-center">
        Al crear tu cuenta aceptás nuestros
        <a href="#" class="text-ink-600 hover:text-ink-900 underline">Términos</a>
        y
        <a href="#" class="text-ink-600 hover:text-ink-900 underline">Política de privacidad</a>
    </div>
</div>
<?php View::endSection(); ?>
