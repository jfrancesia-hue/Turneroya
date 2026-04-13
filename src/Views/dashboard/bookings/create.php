<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<div class="max-w-2xl" x-data="createBooking()">
    <form method="POST" action="/dashboard/bookings" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <?= csrf_field() ?>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Servicio *</label>
            <select name="service_id" x-model="serviceId" @change="loadSlots" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
                <option value="">Elegí un servicio...</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= e($s['id']) ?>"><?= e($s['name']) ?> (<?= (int) $s['duration'] ?>min)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Profesional *</label>
            <select name="professional_id" x-model="professionalId" @change="loadSlots" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
                <option value="">Elegí un profesional...</option>
                <?php foreach ($professionals as $p): ?>
                    <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha *</label>
                <input type="date" name="date" x-model="date" @change="loadSlots" :min="today" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hora *</label>
                <select name="start_time" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300">
                    <option value="">Elegí un horario...</option>
                    <template x-for="s in slots" :key="s.iso">
                        <option :value="s.start" x-text="s.start + ' - ' + s.end"></option>
                    </template>
                </select>
                <div x-show="loadingSlots" class="text-xs text-slate-500 mt-1">Cargando horarios...</div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200">
            <label class="block text-sm font-medium text-slate-700 mb-1">Cliente existente</label>
            <select name="client_id" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 mb-3">
                <option value="">— Crear cliente nuevo —</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?><?= $c['phone'] ? ' · ' . e($c['phone']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="client_name_new" placeholder="Nombre (si es nuevo)" class="px-4 py-2.5 rounded-lg border border-slate-300">
                <input type="tel" name="client_phone_new" placeholder="Teléfono (si es nuevo)" class="px-4 py-2.5 rounded-lg border border-slate-300">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
            <textarea name="notes" rows="2" class="w-full px-4 py-2.5 rounded-lg border border-slate-300"></textarea>
        </div>

        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg font-medium">Crear turno</button>
            <a href="/dashboard/calendar" class="text-slate-600 text-sm">Cancelar</a>
        </div>
    </form>
</div>

<script>
function createBooking() {
    return {
        serviceId: '',
        professionalId: '',
        date: new Date().toISOString().slice(0,10),
        today: new Date().toISOString().slice(0,10),
        slots: [],
        loadingSlots: false,
        async loadSlots() {
            if (!this.serviceId || !this.date) return;
            this.loadingSlots = true;
            this.slots = [];
            try {
                const res = await fetch('/dashboard/api/bookings/available-slots', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        service_id: this.serviceId,
                        professional_id: this.professionalId,
                        date: this.date,
                    })
                });
                const data = await res.json();
                this.slots = data.slots || [];
            } catch(e) {}
            this.loadingSlots = false;
        }
    };
}
</script>
<?php View::endSection(); ?>
