<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('head'); ?>
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>
<?php View::endSection(); ?>

<?php View::section('content'); ?>
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Calendario</h2>
        <p class="text-slate-500 text-sm">Arrastrá, hacé clic y gestioná tus turnos</p>
    </div>
    <a href="/dashboard/bookings/create" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">+ Nuevo turno</a>
</div>

<div class="bg-white rounded-2xl border border-slate-200 p-4">
    <div id="calendar"></div>
</div>

<!-- Modal simple de detalle -->
<div x-data="{open:false,ev:null}" @booking-click.window="open=true;ev=$event.detail" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="open=false">
    <div x-show="open" x-transition class="bg-white rounded-2xl max-w-md w-full p-6" @click.stop>
        <div class="flex items-start justify-between mb-3">
            <div>
                <div class="text-xs text-slate-500 uppercase">Turno</div>
                <div class="text-lg font-semibold" x-text="ev?.title"></div>
            </div>
            <button @click="open=false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">Servicio</span><span x-text="ev?.extendedProps?.service"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Profesional</span><span x-text="ev?.extendedProps?.professional"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Cliente</span><span x-text="ev?.extendedProps?.client"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Teléfono</span><span x-text="ev?.extendedProps?.phone"></span></div>
            <div class="flex justify-between"><span class="text-slate-500">Estado</span><span x-text="ev?.extendedProps?.status"></span></div>
        </div>
        <div class="mt-4 flex gap-2">
            <a :href="'/dashboard/bookings/'+ev?.id" class="flex-1 px-4 py-2 text-center bg-indigo-600 text-white rounded-lg text-sm font-medium">Ver detalle</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('calendar');
    const cal = new FullCalendar.Calendar(el, {
        initialView: 'timeGridWeek',
        locale: 'es',
        timeZone: 'America/Argentina/Buenos_Aires',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        height: 'auto',
        firstDay: 1,
        nowIndicator: true,
        events: '/dashboard/api/calendar/events',
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            window.dispatchEvent(new CustomEvent('booking-click', { detail: {
                id: info.event.id,
                title: info.event.title,
                extendedProps: info.event.extendedProps,
            }}));
        }
    });
    cal.render();
});
</script>
<?php View::endSection(); ?>
