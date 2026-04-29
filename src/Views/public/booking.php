<?php use TurneroYa\Core\View; View::extend('layouts/public'); ?>
<?php View::section('content'); ?>

<div class="min-h-screen bg-ink-50/50">

<!-- Top strip -->
<div class="bg-white border-b border-ink-200/60">
    <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
        <?php View::partial('partials/brand_logo', ['size' => 'sm']); ?>
        <div class="text-xs text-ink-500 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Reserva segura
        </div>
    </div>
</div>

<!-- Business header -->
<div class="relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-accent-700 text-white">
    <div class="absolute inset-0 bg-grid-dark opacity-30"></div>
    <div class="absolute -top-32 -right-32 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-accent-300/20 rounded-full blur-3xl"></div>

    <div class="relative max-w-3xl mx-auto px-4 py-10 text-center">
        <?php if (!empty($business['logo'])): ?>
            <img src="<?= e($business['logo']) ?>" alt="<?= e($business['name']) ?>" class="w-24 h-24 rounded-2xl mx-auto mb-4 object-cover ring-4 ring-white/20 shadow-2xl">
        <?php else: ?>
            <div class="w-24 h-24 rounded-2xl mx-auto mb-4 bg-white/10 backdrop-blur border-2 border-white/20 flex items-center justify-center text-4xl font-extrabold shadow-2xl">
                <?= e(strtoupper(mb_substr((string) $business['name'], 0, 1))) ?>
            </div>
        <?php endif; ?>
        <h1 class="text-3xl font-extrabold tracking-tight"><?= e($business['name']) ?></h1>
        <?php if (!empty($business['description'])): ?>
            <p class="mt-2 text-white/80 text-sm max-w-md mx-auto"><?= e($business['description']) ?></p>
        <?php endif; ?>
        <div class="mt-4 flex items-center justify-center gap-4 text-xs text-white/70">
            <?php if (!empty($business['city'])): ?>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.7 16.7L13.4 21a2 2 0 01-2.8 0l-4.3-4.3a8 8 0 1111.4 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= e($business['city']) ?>
                </div>
            <?php endif; ?>
            <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.1 6.3 7 1-5 4.9 1.2 6.9L12 17.8 5.7 21.1 7 14.2 2 9.3l7-1z"/></svg>
                4.9 · 124 reseñas
            </div>
        </div>
    </div>
</div>

<!-- Alpine booking app -->
<div x-data="bookingApp()" x-cloak class="max-w-2xl mx-auto px-4 py-6 lg:py-10">

    <!-- Progress stepper -->
    <div class="mb-6">
        <div class="flex items-center justify-between relative">
            <div class="absolute top-4 left-0 right-0 h-0.5 bg-ink-200 -z-10"></div>
            <div class="absolute top-4 left-0 h-0.5 bg-brand-600 -z-10 transition-all duration-500" :style="`width: ${((step-1)/3)*100}%`"></div>

            <?php
            $steps = [
                ['Servicio', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['Profesional', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['Horario', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['Datos', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2'],
            ];
            foreach ($steps as $i => [$label, $icon]): $n = $i + 1; ?>
                <div class="flex flex-col items-center gap-1.5" :class="step >= <?= $n ?> ? 'text-brand-700' : 'text-ink-400'">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                         :class="step > <?= $n ?> ? 'bg-brand-600 text-white' : (step === <?= $n ?> ? 'bg-brand-600 text-white ring-4 ring-brand-100' : 'bg-white border-2 border-ink-200 text-ink-400')">
                        <template x-if="step > <?= $n ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <span x-show="step <= <?= $n ?>"><?= $n ?></span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider hidden sm:block"><?= e($label) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Card container -->
    <div class="rounded-3xl bg-white shadow-lift border border-ink-200/60 overflow-hidden">

        <!-- STEP 1: Servicio -->
        <div x-show="step === 1" x-transition class="p-6 sm:p-8">
            <div class="mb-6">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-600 mb-1">Paso 1 de 4</div>
                <h2 class="text-2xl font-extrabold text-ink-900 tracking-tight">¿Qué servicio necesitás?</h2>
                <p class="text-sm text-ink-500 mt-1">Elegí el servicio que querés reservar</p>
            </div>

            <div class="space-y-3">
                <?php foreach ($services as $s): ?>
                    <button type="button"
                            @click="selectService(<?= htmlspecialchars(json_encode([
                                'id' => $s['id'],
                                'name' => $s['name'],
                                'duration' => (int) $s['duration'],
                                'price' => $s['price'],
                                'color' => $s['color'],
                            ]), ENT_QUOTES) ?>)"
                            class="w-full group flex items-center gap-4 p-4 rounded-2xl border-2 border-ink-200 hover:border-brand-500 hover:bg-brand-50/30 transition text-left">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-extrabold text-lg flex-shrink-0 shadow-elev" style="background-color: <?= e($s['color']) ?>">
                            <?= strtoupper(substr($s['name'], 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-ink-900 truncate"><?= e($s['name']) ?></div>
                            <div class="text-xs text-ink-500 flex items-center gap-2 mt-0.5">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= (int) $s['duration'] ?> min
                                </span>
                                <?php if (!empty($s['price'])): ?>
                                    <span>·</span>
                                    <span class="font-semibold text-ink-700"><?= format_money($s['price'], $s['currency']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($s['description'])): ?>
                                <div class="text-xs text-ink-400 mt-1 line-clamp-1"><?= e($s['description']) ?></div>
                            <?php endif; ?>
                        </div>
                        <svg class="w-5 h-5 text-ink-300 group-hover:text-brand-600 transition flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STEP 2: Profesional -->
        <div x-show="step === 2" x-transition class="p-6 sm:p-8">
            <button @click="step = 1" class="mb-4 text-sm text-ink-500 hover:text-ink-900 flex items-center gap-1 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Volver
            </button>
            <div class="mb-6">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-600 mb-1">Paso 2 de 4</div>
                <h2 class="text-2xl font-extrabold text-ink-900 tracking-tight">Elegí un profesional</h2>
                <p class="text-sm text-ink-500 mt-1">¿Con quién querés tu turno?</p>
            </div>

            <div class="space-y-3">
                <button type="button" @click="selectProfessional(null, 'Cualquier disponible')"
                        class="w-full group flex items-center gap-4 p-4 rounded-2xl border-2 border-ink-200 hover:border-brand-500 hover:bg-brand-50/30 transition text-left">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-brand-100 to-accent-100 flex items-center justify-center text-brand-600 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.4-1.9M17 20H7m10 0v-2c0-.7-.1-1.3-.4-1.9M7 20H2v-2a3 3 0 015.4-1.9M7 20v-2c0-.7.1-1.3.4-1.9m0 0a5 5 0 019.3 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-ink-900">Cualquier profesional disponible</div>
                        <div class="text-xs text-ink-500">Te asignamos el primer horario libre</div>
                    </div>
                    <svg class="w-5 h-5 text-ink-300 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>

                <template x-for="p in professionals" :key="p.id">
                    <button type="button" @click="selectProfessional(p.id, p.name)"
                            class="w-full group flex items-center gap-4 p-4 rounded-2xl border-2 border-ink-200 hover:border-brand-500 hover:bg-brand-50/30 transition text-left">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-extrabold text-lg flex-shrink-0 shadow-elev" :style="'background-color: '+p.color">
                            <span x-text="p.name.charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-ink-900 truncate" x-text="p.name"></div>
                            <div class="text-xs text-ink-500 truncate" x-text="p.specialization || 'Profesional'"></div>
                        </div>
                        <svg class="w-5 h-5 text-ink-300 group-hover:text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- STEP 3: Fecha + Hora -->
        <div x-show="step === 3" x-transition class="p-6 sm:p-8">
            <button @click="step = 2" class="mb-4 text-sm text-ink-500 hover:text-ink-900 flex items-center gap-1 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Volver
            </button>
            <div class="mb-6">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-600 mb-1">Paso 3 de 4</div>
                <h2 class="text-2xl font-extrabold text-ink-900 tracking-tight">Elegí día y hora</h2>
                <p class="text-sm text-ink-500 mt-1">Te mostramos solo los horarios disponibles</p>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-2">Fecha</label>
                <input type="date" x-model="selectedDate" @change="loadSlots" :min="today" :max="maxDate"
                       class="focus-ring w-full px-4 py-3.5 rounded-xl border-2 border-ink-200 text-base font-semibold text-ink-900">
            </div>

            <div class="mt-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-2">Horario</label>

                <div x-show="loading" class="text-center py-12">
                    <div class="inline-block w-8 h-8 border-3 border-brand-200 border-t-brand-600 rounded-full animate-spin"></div>
                    <div class="mt-3 text-xs text-ink-500">Buscando horarios...</div>
                </div>

                <div x-show="!loading && slots.length === 0 && selectedDate" class="text-center py-12 rounded-2xl bg-ink-50">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-white flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-ink-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.17 14.83a4 4 0 005.66 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="text-sm font-semibold text-ink-700">No hay horarios disponibles</div>
                    <div class="text-xs text-ink-500 mt-1">Probá con otro día</div>
                </div>

                <div x-show="!loading && slots.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <template x-for="slot in slots" :key="slot.iso">
                        <button type="button" @click="selectSlot(slot)"
                                class="btn-press py-3 rounded-xl border-2 border-ink-200 bg-white hover:border-brand-500 hover:bg-brand-600 hover:text-white font-bold text-sm text-ink-900 transition">
                            <span x-text="slot.start"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- STEP 4: Datos -->
        <div x-show="step === 4" x-transition class="p-6 sm:p-8">
            <button @click="step = 3" class="mb-4 text-sm text-ink-500 hover:text-ink-900 flex items-center gap-1 font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Volver
            </button>
            <div class="mb-6">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-600 mb-1">Paso 4 de 4</div>
                <h2 class="text-2xl font-extrabold text-ink-900 tracking-tight">Casi listo...</h2>
                <p class="text-sm text-ink-500 mt-1">Sólo nos falta confirmar tus datos</p>
            </div>

            <!-- Summary card -->
            <div class="mb-6 p-5 rounded-2xl bg-gradient-to-br from-brand-50 to-accent-50 border border-brand-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-700 mb-3">Tu reserva</div>
                <div class="space-y-2.5 text-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-ink-500 font-semibold">Servicio</div>
                            <div class="font-bold text-ink-900" x-text="service?.name"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-ink-500 font-semibold">Profesional</div>
                            <div class="font-bold text-ink-900" x-text="professional?.name || 'Cualquier disponible'"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center">
                            <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase text-ink-500 font-semibold">Cuándo</div>
                            <div class="font-bold text-ink-900" x-text="formatDateLong(selectedDate) + ' · ' + selectedSlot?.start + 'hs'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitBooking" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Tu nombre *</label>
                    <input type="text" x-model="form.client_name" required minlength="2"
                           placeholder="¿Cómo te llamás?"
                           class="focus-ring w-full px-4 py-3.5 rounded-xl border-2 border-ink-200 bg-white text-ink-900 placeholder-ink-400 text-base">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">WhatsApp *</label>
                    <input type="tel" x-model="form.client_phone" required
                           placeholder="+54 9 11 1234-5678"
                           class="focus-ring w-full px-4 py-3.5 rounded-xl border-2 border-ink-200 bg-white text-ink-900 placeholder-ink-400 text-base">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Email <span class="text-ink-300 font-normal normal-case">(opcional)</span></label>
                    <input type="email" x-model="form.client_email"
                           placeholder="tu@email.com"
                           class="focus-ring w-full px-4 py-3.5 rounded-xl border-2 border-ink-200 bg-white text-ink-900 placeholder-ink-400 text-base">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-ink-500 mb-1.5">Notas <span class="text-ink-300 font-normal normal-case">(opcional)</span></label>
                    <textarea x-model="form.notes" rows="2"
                              placeholder="Algo que el profesional deba saber..."
                              class="focus-ring w-full px-4 py-3 rounded-xl border-2 border-ink-200 bg-white text-ink-900 placeholder-ink-400 text-sm"></textarea>
                </div>

                <div x-show="error" x-cloak class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-sm text-red-700" x-text="error"></div>

                <button type="submit" :disabled="submitting"
                        class="btn-press w-full py-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-brand disabled:opacity-60 transition">
                    <span x-show="!submitting" class="flex items-center justify-center gap-2">
                        Confirmar mi turno
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span x-show="submitting" class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/></svg>
                        Confirmando...
                    </span>
                </button>
            </form>
        </div>
    </div>

    <!-- Trust footer -->
    <div class="mt-6 text-center text-xs text-ink-400 flex items-center justify-center gap-3">
        <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.6 4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Sin cargo
        </span>
        <span>·</span>
        <span class="flex items-center gap-1">
            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Datos protegidos
        </span>
        <span>·</span>
        <span>Powered by <span class="font-bold text-brand-600">TurneroYa</span></span>
    </div>
</div>

</div>

<script>
function bookingApp() {
    const today = new Date().toISOString().slice(0,10);
    const max = new Date();
    max.setDate(max.getDate() + <?= (int) $business['max_advance_days'] ?>);
    const csrfToken = <?= json_encode($bookingToken ?? '') ?>;
    return {
        step: 1,
        today,
        maxDate: max.toISOString().slice(0,10),
        service: null,
        professional: null,
        professionals: [],
        selectedDate: '',
        selectedSlot: null,
        slots: [],
        loading: false,
        submitting: false,
        error: '',
        form: { client_name: '', client_phone: '', client_email: '', notes: '' },

        async selectService(svc) {
            this.service = svc;
            try {
                const res = await fetch('/book/<?= e($business['slug']) ?>/slots', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Booking-Token': csrfToken},
                    body: new URLSearchParams({service_id: svc.id, date: today})
                });
                const data = await res.json();
                this.professionals = data.professionals || [];
            } catch(e) { this.professionals = []; }
            this.step = 2;
            window.scrollTo({top: 0, behavior: 'smooth'});
        },

        selectProfessional(id, name) {
            this.professional = { id, name };
            this.selectedDate = this.today;
            this.step = 3;
            this.loadSlots();
            window.scrollTo({top: 0, behavior: 'smooth'});
        },

        async loadSlots() {
            if (!this.service || !this.selectedDate) return;
            this.loading = true;
            this.slots = [];
            try {
                const res = await fetch('/book/<?= e($business['slug']) ?>/slots', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Booking-Token': csrfToken},
                    body: new URLSearchParams({
                        service_id: this.service.id,
                        professional_id: this.professional?.id || '',
                        date: this.selectedDate
                    })
                });
                const data = await res.json();
                this.slots = data.slots || [];
            } catch(e) { this.error = 'Error al cargar horarios'; }
            this.loading = false;
        },

        selectSlot(slot) {
            this.selectedSlot = slot;
            if (slot.professional_id && !this.professional?.id) {
                this.professional = { id: slot.professional_id, name: slot.professional_name };
            }
            this.step = 4;
            window.scrollTo({top: 0, behavior: 'smooth'});
        },

        formatDateLong(d) {
            if (!d) return '';
            return new Date(d+'T00:00:00').toLocaleDateString('es-AR', {weekday:'long', day:'numeric', month:'long'});
        },

        async submitBooking() {
            this.submitting = true;
            this.error = '';
            try {
                const payload = new URLSearchParams({
                    service_id: this.service.id,
                    professional_id: this.professional?.id || '',
                    date: this.selectedDate,
                    start_time: this.selectedSlot.start,
                    ...this.form,
                });
                const res = await fetch('/book/<?= e($business['slug']) ?>/confirm', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Booking-Token': csrfToken},
                    body: payload
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.error = data.error || 'Error al confirmar';
                }
            } catch(e) {
                this.error = 'Error de red. Intentá de nuevo.';
            }
            this.submitting = false;
        }
    };
}
</script>
<?php View::endSection(); ?>
