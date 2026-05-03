<?php use TurneroYa\Core\View; View::extend('layouts/auth'); ?>
<?php View::section('content'); ?>
<div>
    <h1 class="text-3xl font-extrabold text-ink-900">Entrar a Reservia</h1>
    <p class="mt-2 text-sm text-ink-500">
        Tu recepcionista digital ya esta lista.
        <a href="/register" class="font-semibold text-brand-600 hover:text-brand-700">Crear cuenta gratis</a>
    </p>
</div>

<form method="POST" action="/login" class="mt-10 space-y-5">
    <?= csrf_field() ?>

    <div>
        <label class="block text-sm font-semibold text-ink-700 mb-1.5">Email</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.9 5.3a2 2 0 0 0 2.2 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/>
                </svg>
            </div>
            <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus
                   placeholder="tu@email.com"
                   class="focus-ring w-full pl-11 pr-4 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-1.5">
            <label class="block text-sm font-semibold text-ink-700">Contrasena</label>
            <a href="#" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Olvidaste?</a>
        </div>
        <div class="relative" x-data="{show: false}">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-ink-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z"/>
                </svg>
            </div>
            <input :type="show ? 'text' : 'password'" name="password" required minlength="6"
                   placeholder="Tu clave"
                   class="focus-ring w-full pl-11 pr-11 py-3 rounded-lg border border-ink-200 bg-white text-ink-900 placeholder-ink-400 transition">
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-ink-400 hover:text-ink-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12C4 7.5 7.7 4.5 12 4.5s8 3 9.5 7.5c-1.5 4.5-5.2 7.5-9.5 7.5s-8-3-9.5-7.5z"/></svg>
            </button>
        </div>
    </div>

    <button type="submit" class="btn-press w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg font-semibold shadow-brand transition">
        Entrar a mi panel
    </button>
</form>

<p class="mt-8 text-center text-xs text-ink-400">
    Al continuar aceptas nuestros <a href="/terms" class="text-ink-600 hover:text-ink-900 underline">Terminos</a> y <a href="/privacy" class="text-ink-600 hover:text-ink-900 underline">Privacidad</a>.
</p>
<?php View::endSection(); ?>
