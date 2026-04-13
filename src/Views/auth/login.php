<?php use TurneroYa\Core\View; View::extend('layouts/auth'); ?>
<?php View::section('content'); ?>
<div>
    <h1 class="text-3xl font-extrabold tracking-tight text-ink-900">Iniciar sesión</h1>
    <p class="mt-2 text-sm text-ink-500">
        ¿No tenés cuenta?
        <a href="/register" class="font-semibold text-brand-600 hover:text-brand-700">Crear una gratis →</a>
    </p>
</div>

<form method="POST" action="/login" class="mt-10 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Email</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus
                   placeholder="tu@email.com"
                   class="focus-ring w-full pl-11 pr-4 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-semibold text-ink-700">Contraseña</label>
            <a href="#" class="text-xs font-semibold text-brand-600 hover:text-brand-700">¿Olvidaste?</a>
        </div>
        <div class="relative" x-data="{show: false}">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <input :type="show ? 'text' : 'password'" name="password" required minlength="6"
                   placeholder="••••••••"
                   class="focus-ring w-full pl-11 pr-11 py-3 rounded-xl border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-ink-400 hover:text-ink-700">
                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12C4 7.5 7.7 4.5 12 4.5s8 3 9.5 7.5c-1.5 4.5-5.2 7.5-9.5 7.5s-8-3-9.5-7.5z"/></svg>
                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.9 18.5a9.8 9.8 0 01-1.9.2C7.7 18.7 4 15.6 2.5 11.2c.5-1.4 1.2-2.7 2.1-3.8m3.4-2.7a9.8 9.8 0 014-.9c4.3 0 8 3 9.5 7.5a10 10 0 01-1.5 2.8M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18"/></svg>
            </button>
        </div>
    </div>

    <button type="submit" class="btn-press w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-semibold shadow-brand transition">
        Entrar a mi panel
    </button>
</form>

<div class="mt-8 pt-8 border-t border-ink-100">
    <div class="text-xs text-ink-400 text-center">
        Al continuar aceptás nuestros
        <a href="#" class="text-ink-600 hover:text-ink-900 underline">Términos</a>
        y
        <a href="#" class="text-ink-600 hover:text-ink-900 underline">Política de privacidad</a>
    </div>
</div>
<?php View::endSection(); ?>
