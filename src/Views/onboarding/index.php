<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<div class="min-h-screen bg-ink-50/50">

<!-- Top strip -->
<div class="bg-white border-b border-ink-200/60">
    <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
        <?php View::partial('partials/brand_logo', ['size' => 'sm']); ?>
        <form method="POST" action="/logout">
            <?= csrf_field() ?>
            <button type="submit" class="text-xs text-ink-500 hover:text-ink-900 font-semibold">Salir</button>
        </form>
    </div>
</div>

<div x-data="onboardingWizard()" x-cloak class="max-w-2xl mx-auto px-4 py-8 lg:py-12">

    <!-- Intro -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-100">
            <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
            <span class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Configuración inicial</span>
        </div>
        <h1 class="mt-4 text-3xl sm:text-4xl font-extrabold tracking-tight text-ink-900">
            Bienvenido a <span class="text-gradient">Reservia</span>
        </h1>
        <p class="mt-3 text-ink-500 text-base">Configuremos tu negocio en menos de 3 minutos ⏱️</p>
    </div>

    <!-- Progress bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-2 text-xs font-bold uppercase tracking-wider text-ink-500">
            <span>Paso <span x-text="step"></span> de 4</span>
            <span x-text="Math.round((step/4)*100) + '% completo'"></span>
        </div>
        <div class="h-2 bg-ink-200 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-brand-600 to-accent-600 transition-all duration-500" :style="`width: ${(step/4)*100}%`"></div>
        </div>
    </div>

    <form method="POST" action="/dashboard/onboarding">
        <?= csrf_field() ?>

        <div class="rounded-3xl bg-white shadow-lift border border-ink-200/60 overflow-hidden">

            <!-- STEP 1: Negocio -->
            <div x-show="step === 1" x-transition class="p-6 sm:p-8">
                <div class="mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.3V21a2 2 0 01-2 2H5a2 2 0 01-2-2v-7.7M16 5l-4-4-4 4M12 1v14"/></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-ink-900">Contanos sobre tu negocio</h2>
                    <p class="text-sm text-ink-500 mt-1">Estos datos aparecerán en tu página pública</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Nombre del negocio *</label>
                        <input type="text" name="business_name" required placeholder="Ej: Belleza Pura"
                               class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-base font-semibold text-ink-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Rubro *</label>
                        <select name="business_type" class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 bg-white text-base font-semibold text-ink-900">
                            <option value="SALON">💇 Peluquería / Estética</option>
                            <option value="CLINIC">🏥 Clínica / Consultorio</option>
                            <option value="DENTIST">🦷 Odontología</option>
                            <option value="VET">🐶 Veterinaria</option>
                            <option value="GYM">💪 Gimnasio / Entrenamiento</option>
                            <option value="STUDIO">📸 Estudio (fotografía, música)</option>
                            <option value="WORKSHOP">🔧 Taller (mecánico, carpintería)</option>
                            <option value="LAWYER">⚖️ Estudio jurídico</option>
                            <option value="ACCOUNTANT">💰 Contador</option>
                            <option value="OTHER">✨ Otro</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Teléfono</label>
                            <input type="tel" name="phone" placeholder="+54 11..." class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">WhatsApp</label>
                            <input type="tel" name="whatsapp" placeholder="+54 9 11..." class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Ciudad</label>
                        <input type="text" name="city" placeholder="Ciudad Autónoma de Buenos Aires"
                               class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-sm">
                    </div>
                </div>
            </div>

            <!-- STEP 2: Servicio -->
            <div x-show="step === 2" x-transition class="p-6 sm:p-8">
                <div class="mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-accent-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-accent-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-ink-900">Tu primer servicio</h2>
                    <p class="text-sm text-ink-500 mt-1">Podés agregar más después</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Nombre del servicio *</label>
                        <input type="text" name="service_name" required placeholder="Ej: Corte de pelo"
                               class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-base font-semibold text-ink-900">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Duración *</label>
                            <div class="relative">
                                <input type="number" name="service_duration" value="30" min="5" required
                                       class="focus-ring w-full pl-4 pr-14 py-3 rounded-xl border-2 border-ink-200 text-sm font-semibold">
                                <span class="absolute inset-y-0 right-3 flex items-center text-xs font-bold text-ink-400">MIN</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Precio</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-xs font-bold text-ink-400">AR$</span>
                                <input type="number" name="service_price" step="0.01" value="0"
                                       class="focus-ring w-full pl-12 pr-4 py-3 rounded-xl border-2 border-ink-200 text-sm font-semibold">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Profesional -->
            <div x-show="step === 3" x-transition class="p-6 sm:p-8">
                <div class="mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-ink-900">Primer profesional</h2>
                    <p class="text-sm text-ink-500 mt-1">Puede ser tu nombre o el de alguien de tu equipo</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Nombre *</label>
                    <input type="text" name="professional_name" required placeholder="Ej: María García"
                           class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-base font-semibold text-ink-900">
                    <p class="mt-2 text-xs text-ink-400">Podés agregar más profesionales más tarde desde el panel.</p>
                </div>
            </div>

            <!-- STEP 4: Horarios -->
            <div x-show="step === 4" x-transition class="p-6 sm:p-8">
                <div class="mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-ink-900">Horario de atención</h2>
                    <p class="text-sm text-ink-500 mt-1">¿Cuándo atendés?</p>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Apertura</label>
                            <input type="time" name="start_hour" value="09:00" required
                                   class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Cierre</label>
                            <input type="time" name="end_hour" value="18:00" required
                                   class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 text-sm font-semibold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-2">Días activos</label>
                        <div class="grid grid-cols-7 gap-2">
                            <?php
                            $dayLabels = ['D','L','M','M','J','V','S'];
                            $defaults = [1,2,3,4,5];
                            foreach ($dayLabels as $i => $label):
                            ?>
                                <label class="relative cursor-pointer">
                                    <input type="checkbox" name="active_days[]" value="<?= $i ?>"
                                           <?= in_array($i, $defaults, true) ? 'checked' : '' ?>
                                           class="peer sr-only">
                                    <div class="text-center py-3 rounded-xl border-2 border-ink-200 bg-white text-ink-400 font-bold text-sm transition
                                                peer-checked:bg-brand-600 peer-checked:text-white peer-checked:border-brand-600 peer-checked:shadow-brand">
                                        <?= $label ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer nav -->
            <div class="px-6 sm:px-8 py-5 bg-ink-50/50 border-t border-ink-100 flex items-center justify-between">
                <button type="button" @click="step = Math.max(1, step-1)" x-show="step > 1"
                        class="btn-press px-4 py-2.5 text-sm font-bold text-ink-600 hover:text-ink-900">
                    ← Volver
                </button>
                <div x-show="step === 1"></div>

                <button type="button" @click="step = step+1" x-show="step < 4"
                        class="btn-press px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-sm font-bold shadow-brand ml-auto">
                    Continuar →
                </button>
                <button type="submit" x-show="step === 4"
                        class="btn-press px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white rounded-xl text-sm font-bold shadow-elev ml-auto inline-flex items-center gap-2">
                    Finalizar configuración
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>
    </form>
</div>

</div>

<script>
function onboardingWizard() {
    return {
        step: 1,
    };
}
</script>
<?php View::endSection(); ?>
