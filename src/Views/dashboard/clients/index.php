<?php use TurneroYa\Core\View; View::extend('layouts/dashboard'); ?>
<?php View::section('content'); ?>
<?php
$totalClients = count($clients);
$vipClients = count(array_filter($clients, fn($c) => (int) ($c['total_bookings'] ?? 0) >= 3));
$riskClients = count(array_filter($clients, fn($c) => (int) ($c['no_show_count'] ?? 0) > 0));
$heroImage = 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=80';
?>

<section class="clients-hero">
    <div>
        <span>CRM Reservia</span>
        <h2>Clientes con historial, señales y próxima acción.</h2>
        <p>Un panel pensado para reconocer a quien vuelve, cuidar a quien falla y convertir cada contacto en una nueva reserva.</p>
    </div>
    <img src="<?= e($heroImage) ?>" alt="" loading="lazy">
</section>

<section class="clients-toolbar">
    <div class="client-mini-stat">
        <strong><?= $totalClients ?></strong>
        <span>clientes</span>
    </div>
    <div class="client-mini-stat">
        <strong><?= $vipClients ?></strong>
        <span>recurrentes</span>
    </div>
    <div class="client-mini-stat">
        <strong><?= $riskClients ?></strong>
        <span>con no-show</span>
    </div>
    <form method="GET" class="client-search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar cliente, teléfono o email">
        <button>Buscar</button>
    </form>
    <a href="/dashboard/clients/create" class="client-new-action">Nuevo cliente</a>
</section>

<?php if (empty($clients)): ?>
    <section class="dash-empty-state is-large">
        <div class="ai-orb-3d medium"><div class="ai-orb-core"></div><div class="ai-orb-ring ring-a"></div><div class="ai-orb-ring ring-b"></div></div>
        <strong>Tu CRM está listo</strong>
        <p>Cuando cargues clientes, Reservia los va a mostrar con historial, contacto y riesgo de ausencia.</p>
        <a href="/dashboard/clients/create">Crear cliente</a>
    </section>
<?php else: ?>
    <section class="clients-grid">
        <?php foreach ($clients as $client): ?>
            <?php
            $bookings = (int) ($client['total_bookings'] ?? 0);
            $noShows = (int) ($client['no_show_count'] ?? 0);
            $initial = mb_strtoupper(mb_substr((string) $client['name'], 0, 1));
            $score = $noShows > 0 ? 'Riesgo' : ($bookings >= 3 ? 'VIP' : 'Nuevo');
            $scoreClass = $noShows > 0 ? 'is-danger' : ($bookings >= 3 ? 'is-confirmed' : 'is-pending');
            ?>
            <a href="/dashboard/clients/<?= e($client['id']) ?>" class="client-rich-card">
                <div class="client-card-head">
                    <div class="client-avatar big"><?= e($initial) ?></div>
                    <div>
                        <strong><?= e($client['name']) ?></strong>
                        <span><?= e($client['phone'] ?: $client['whatsapp_number'] ?: 'Sin teléfono') ?></span>
                    </div>
                    <em class="<?= e($scoreClass) ?>"><?= e($score) ?></em>
                </div>
                <div class="client-contact-line">
                    <span><?= e($client['email'] ?: 'Sin email') ?></span>
                </div>
                <div class="client-card-metrics">
                    <div><strong><?= $bookings ?></strong><span>turnos</span></div>
                    <div><strong><?= $noShows ?></strong><span>no-show</span></div>
                    <div><strong><?= !empty($client['last_visit']) ? format_date($client['last_visit'], 'd/m') : '—' ?></strong><span>última visita</span></div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php View::endSection(); ?>
