<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('head'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<?php View::endSection(); ?>
<?php View::section('content'); ?>
<div x-data="analytics()" x-init="load()">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Analytics</h2>
        <select x-model="days" @change="load()" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
            <option value="7">Últimos 7 días</option>
            <option value="30" selected>Últimos 30 días</option>
            <option value="90">Últimos 90 días</option>
        </select>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-700 mb-4">Turnos por día</h3>
            <canvas id="chartDaily" height="80"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-700 mb-4">Servicios más pedidos</h3>
            <canvas id="chartServices" height="80"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-700 mb-4">Horarios pico</h3>
            <canvas id="chartHours" height="80"></canvas>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-700 mb-4">Origen de reservas</h3>
            <canvas id="chartSource" height="80"></canvas>
        </div>
    </div>
</div>

<script>
let charts = {};
function analytics() {
    return {
        days: 30,
        async load() {
            const res = await fetch('/dashboard/api/analytics/data?days=' + this.days);
            const data = await res.json();

            // Destruir gráficos previos
            Object.values(charts).forEach(c => c && c.destroy());

            // Daily
            charts.daily = new Chart(document.getElementById('chartDaily'), {
                type: 'line',
                data: {
                    labels: (data.byDay || []).map(d => d.day),
                    datasets: [
                        {label:'Total', data:(data.byDay||[]).map(d => d.total), borderColor:'#4f46e5', backgroundColor:'rgba(79,70,229,.1)', tension:.3, fill:true},
                        {label:'No-shows', data:(data.byDay||[]).map(d => d.no_shows), borderColor:'#ef4444', tension:.3},
                    ]
                },
                options: { responsive:true, maintainAspectRatio:false }
            });

            // Services
            charts.services = new Chart(document.getElementById('chartServices'), {
                type: 'bar',
                data: {
                    labels: (data.topServices||[]).map(s => s.name),
                    datasets: [{label:'Turnos', data:(data.topServices||[]).map(s => s.total), backgroundColor:'#10b981'}]
                },
                options: { responsive:true, maintainAspectRatio:false, indexAxis: 'y' }
            });

            // Hours
            charts.hours = new Chart(document.getElementById('chartHours'), {
                type: 'bar',
                data: {
                    labels: (data.peakHours||[]).map(h => h.hour + ':00'),
                    datasets: [{label:'Turnos', data:(data.peakHours||[]).map(h => h.total), backgroundColor:'#a855f7'}]
                },
                options: { responsive:true, maintainAspectRatio:false }
            });

            // Source
            charts.source = new Chart(document.getElementById('chartSource'), {
                type: 'doughnut',
                data: {
                    labels: (data.bySource||[]).map(s => s.source),
                    datasets: [{data:(data.bySource||[]).map(s => s.total), backgroundColor:['#4f46e5','#10b981','#f59e0b','#ef4444','#06b6d4']}]
                },
                options: { responsive:true, maintainAspectRatio:false }
            });
        }
    };
}
</script>
<?php View::endSection(); ?>
