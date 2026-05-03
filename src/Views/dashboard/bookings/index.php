<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<?php
$filterLabels = ['upcoming' => 'Próximos', 'today' => 'Hoy', 'past' => 'Historial'];
$statusMeta = [
    'PENDING' => ['Pendiente', 'is-pending'],
    'CONFIRMED' => ['Confirmado', 'is-confirmed'],
    'COMPLETED' => ['Completado', 'is-completed'],
    'CANCELLED' => ['Cancelado', 'is-muted'],
    'NO_SHOW' => ['No-show', 'is-danger'],
    'RESCHEDULED' => ['Reprogramado', 'is-rescheduled'],
];
$columns = [
    'PENDING' => [],
    'CONFIRMED' => [],
    'COMPLETED' => [],
];
foreach ($bookings as $booking) {
    $status = (string) ($booking['status'] ?? 'PENDING');
    $columns[$status][] = $booking;
}
$heroImage = 'https://images.unsplash.com/photo-1556745757-8d76bdb6984b?auto=format&fit=crop&w=1200&q=80';
?>

<section class="bookings-hero">
    <div>
        <span>Turnos</span>
        <h2>Pipeline visual de reservas</h2>
        <p>Cada turno como una oportunidad: pendiente, confirmado, atendido o recuperado.</p>
        <div class="booking-filter-pills">
            <?php foreach ($filterLabels as $key => $label): ?>
                <a href="?filter=<?= e($key) ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <img src="<?= e($heroImage) ?>" alt="" loading="lazy">
    <a href="/dashboard/bookings/create" class="bookings-hero-action">Nuevo turno</a>
</section>

<?php if (empty($bookings)): ?>
    <section class="dash-empty-state is-large">
        <div class="ai-orb-3d medium"><div class="ai-orb-core"></div><div class="ai-orb-ring ring-a"></div><div class="ai-orb-ring ring-b"></div></div>
        <strong>No hay turnos en este filtro</strong>
        <p>Cuando entren reservas, Reservia las va a ordenar por estado para que tu equipo accione rápido.</p>
        <a href="/dashboard/bookings/create">Crear turno</a>
    </section>
<?php else: ?>
    <section class="booking-kanban">
        <?php foreach ([
            'PENDING' => ['Pendientes', 'Necesitan confirmación'],
            'CONFIRMED' => ['Confirmados', 'Listos para atender'],
            'COMPLETED' => ['Completados', 'Historial de valor'],
        ] as $status => [$columnTitle, $subtitle]): ?>
            <article class="booking-column">
                <header>
                    <div>
                        <span><?= e($subtitle) ?></span>
                        <h3><?= e($columnTitle) ?></h3>
                    </div>
                    <strong><?= count($columns[$status] ?? []) ?></strong>
                </header>
                <div class="booking-card-list">
                    <?php foreach (($columns[$status] ?? []) as $b): ?>
                        <?php [$statusLabel, $statusClass] = $statusMeta[$b['status']] ?? [$b['status'], 'is-muted']; ?>
                        <a href="/dashboard/bookings/<?= e($b['id']) ?>" class="booking-rich-card">
                            <div class="booking-card-top">
                                <div class="client-avatar"><?= e(mb_strtoupper(mb_substr((string) $b['client_name'], 0, 1))) ?></div>
                                <div>
                                    <strong><?= e($b['client_name']) ?></strong>
                                    <span><?= format_date($b['date'], 'd/m') ?> · <?= e(substr($b['start_time'], 0, 5)) ?></span>
                                </div>
                                <em class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></em>
                            </div>
                            <div class="booking-service-line">
                                <i style="background: <?= e($b['service_color'] ?? '#00a884') ?>"></i>
                                <span><?= e($b['service_name']) ?></span>
                            </div>
                            <div class="booking-card-foot">
                                <span><?= e($b['professional_name'] ?? 'Equipo') ?></span>
                                <b>#<?= e($b['booking_number'] ?? '') ?></b>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php
    $otherBookings = array_values(array_filter($bookings, fn($b) => !in_array((string) $b['status'], ['PENDING', 'CONFIRMED', 'COMPLETED'], true)));
    ?>
    <?php if (!empty($otherBookings)): ?>
        <section class="dash-panel mt-6">
            <div class="dash-panel-head">
                <div>
                    <span>Otros estados</span>
                    <h3>Cancelados y reprogramados</h3>
                </div>
            </div>
            <div class="booking-compact-list">
                <?php foreach ($otherBookings as $b): ?>
                    <?php [$statusLabel, $statusClass] = $statusMeta[$b['status']] ?? [$b['status'], 'is-muted']; ?>
                    <a href="/dashboard/bookings/<?= e($b['id']) ?>">
                        <strong><?= e($b['client_name']) ?></strong>
                        <span><?= e($b['service_name']) ?> · <?= format_date($b['date'], 'd/m') ?> <?= e(substr($b['start_time'], 0, 5)) ?></span>
                        <em class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></em>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($pagination) && $pagination['pages'] > 1): ?>
    <?php
    $page = (int) $pagination['page'];
    $pages = (int) $pagination['pages'];
    $prev = max(1, $page - 1);
    $next = min($pages, $page + 1);
    $base = '?filter=' . urlencode($filter);
    ?>
    <div class="dash-pagination">
        <span>Página <strong><?= $page ?></strong> de <?= $pages ?></span>
        <div>
            <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $base ?>&page=<?= $prev ?>">Anterior</a>
            <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="<?= $base ?>&page=<?= $next ?>">Siguiente</a>
        </div>
    </div>
<?php endif; ?>

<?php View::endSection(); ?>
