<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<?php
$today = new DateTimeImmutable('today');
$statusCounts = ['PENDING' => 0, 'CONFIRMED' => 0, 'COMPLETED' => 0, 'CANCELLED' => 0, 'NO_SHOW' => 0];
foreach ($upcoming as $booking) {
    $status = (string) ($booking['status'] ?? 'PENDING');
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
}
$nextBooking = $upcoming[0] ?? null;
$occupancy = min(96, max(18, ((int) $stats['bookings_week']) * 7));
$assistScore = min(99, 86 + (int) $stats['professionals_active']);
$heroImages = [
    'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=900&q=80',
    'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?auto=format&fit=crop&w=900&q=80',
];
?>

<section class="dash-hero">
    <div class="dash-hero-copy">
        <div class="dash-kicker">
            <span></span>
            <?= e($business['name']) ?> · <?= format_date('now', 'd/m/Y') ?>
        </div>
        <h2>Tu operación de hoy, con Reservia al mando.</h2>
        <p>Agenda, clientes, cobros y WhatsApp en una vista ejecutiva pensada para vender más turnos sin mirar una planilla.</p>

        <div class="dash-hero-actions">
            <a href="/dashboard/bookings/create" class="dash-primary-action">
                Nuevo turno
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
            </a>
            <a href="<?= e(url('/book/' . $business['slug'])) ?>" target="_blank" class="dash-secondary-action">Ver link público</a>
        </div>
    </div>

    <div class="dash-hero-visual" aria-hidden="true">
        <div class="ai-orb-3d">
            <div class="ai-orb-core"></div>
            <div class="ai-orb-ring ring-a"></div>
            <div class="ai-orb-ring ring-b"></div>
            <div class="ai-orb-ring ring-c"></div>
        </div>
        <div class="dash-phone-glass">
            <div class="dash-phone-head">
                <span></span>
                <strong>Reservia Bot</strong>
                <em>online</em>
            </div>
            <div class="bot-bubble">Tengo 16:30 disponible con Sofía.</div>
            <div class="user-bubble">Confirmame y pasame la seña</div>
            <div class="bot-bubble">Listo. Turno reservado y pago generado.</div>
        </div>
        <div class="dash-photo-stack">
            <?php foreach ($heroImages as $i => $src): ?>
                <img src="<?= e($src) ?>" alt="" class="dash-real-photo photo-<?= $i + 1 ?>" loading="lazy">
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="dash-metrics-grid">
    <?php
    $cards = [
        ['Turnos hoy', (int) $stats['bookings_today'], 'Agenda activa', 'bg-emerald-500', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ['Semana', (int) $stats['bookings_week'], $occupancy . '% ocupación', 'bg-lime-400', 'M4 19V5m6 14V9m6 10v-7m4 7H2'],
        ['Clientes', (int) $stats['clients_total'], 'CRM vivo', 'bg-accent-500', 'M17 20h5v-2a3 3 0 00-5.4-1.9M7 20H2v-2a3 3 0 015.4-1.9M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['Asistencia', $assistScore . '%', 'Score estimado', 'bg-sky-500', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ];
    foreach ($cards as [$label, $value, $meta, $color, $icon]): ?>
        <article class="dash-metric-card">
            <div class="dash-metric-icon <?= $color ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= e($icon) ?>"/></svg>
            </div>
            <strong><?= e($value) ?></strong>
            <span><?= e($label) ?></span>
            <small><?= e($meta) ?></small>
        </article>
    <?php endforeach; ?>
</section>

<section class="dash-command-grid">
    <article class="dash-panel dash-agenda-panel">
        <div class="dash-panel-head">
            <div>
                <span>Agenda viva</span>
                <h3>Próximos turnos</h3>
            </div>
            <a href="/dashboard/calendar">Calendario →</a>
        </div>

        <?php if (empty($upcoming)): ?>
            <div class="dash-empty-state">
                <div class="ai-orb-3d small"><div class="ai-orb-core"></div><div class="ai-orb-ring ring-a"></div><div class="ai-orb-ring ring-b"></div></div>
                <strong>Agenda limpia</strong>
                <p>Creá el primer turno y Reservia empieza a ordenar el día.</p>
                <a href="/dashboard/bookings/create">Crear turno</a>
            </div>
        <?php else: ?>
            <div class="dash-timeline">
                <?php foreach ($upcoming as $b): ?>
                    <?php
                    $statusMap = [
                        'CONFIRMED' => ['Confirmado', 'is-confirmed'],
                        'PENDING' => ['Pendiente', 'is-pending'],
                        'COMPLETED' => ['Completado', 'is-completed'],
                        'CANCELLED' => ['Cancelado', 'is-muted'],
                        'NO_SHOW' => ['No-show', 'is-danger'],
                    ];
                    [$statusLabel, $statusClass] = $statusMap[$b['status']] ?? [$b['status'], 'is-muted'];
                    ?>
                    <a href="/dashboard/bookings/<?= e($b['id']) ?>" class="dash-turn-card">
                        <div class="turn-date">
                            <span><?= format_date($b['date'], 'M') ?></span>
                            <strong><?= format_date($b['date'], 'd') ?></strong>
                        </div>
                        <div class="turn-body">
                            <strong><?= e($b['client_name']) ?></strong>
                            <span>
                                <i style="background: <?= e($b['service_color'] ?? '#00a884') ?>"></i>
                                <?= e($b['service_name']) ?> · <?= e($b['professional_name'] ?? 'Equipo') ?>
                            </span>
                        </div>
                        <div class="turn-side">
                            <strong><?= e(substr($b['start_time'], 0, 5)) ?></strong>
                            <em class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></em>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <aside class="dash-side-stack">
        <article class="dash-panel dash-ai-panel">
            <div class="dash-panel-head compact">
                <div>
                    <span>IA WhatsApp</span>
                    <h3>Recepción automática</h3>
                </div>
            </div>
            <div class="ai-orb-3d medium">
                <div class="ai-orb-core"></div>
                <div class="ai-orb-ring ring-a"></div>
                <div class="ai-orb-ring ring-b"></div>
                <div class="ai-orb-ring ring-c"></div>
            </div>
            <p>Respuestas, horarios disponibles, señas y confirmaciones sin fricción.</p>
            <a href="/dashboard/bot/config">Ajustar bot →</a>
        </article>

        <article class="dash-panel dash-pipeline-panel">
            <div class="dash-panel-head compact">
                <div>
                    <span>Pipeline</span>
                    <h3>Estado de turnos</h3>
                </div>
            </div>
            <div class="pipeline-bars">
                <?php foreach ([
                    ['Pendientes', $statusCounts['PENDING'] ?? 0, 'pending'],
                    ['Confirmados', $statusCounts['CONFIRMED'] ?? 0, 'confirmed'],
                    ['Completados', $statusCounts['COMPLETED'] ?? 0, 'completed'],
                ] as [$label, $count, $class]): ?>
                    <div class="pipeline-row">
                        <span><?= e($label) ?></span>
                        <strong><?= (int) $count ?></strong>
                        <i class="<?= e($class) ?>" style="width: <?= max(12, min(100, ((int) $count + 1) * 18)) ?>%"></i>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </aside>
</section>

<section class="dash-action-strip">
    <?php foreach ([
        ['/dashboard/clients/create', 'Nuevo cliente', 'Sumá datos y hábitos al CRM'],
        ['/dashboard/services/create', 'Nuevo servicio', 'Precio, duración y color propio'],
        ['/dashboard/blockouts', 'Bloquear horario', 'Protegé huecos y descansos'],
        ['/dashboard/analytics', 'Ver analytics', 'Detectá horas y servicios fuertes'],
    ] as [$url, $title, $copy]): ?>
        <a href="<?= e($url) ?>">
            <strong><?= e($title) ?></strong>
            <span><?= e($copy) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<?php View::endSection(); ?>
