<?php use TurneroYa\Core\View; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<title><?= e($title ?? 'Reservia · Agenda inteligente con IA') ?></title>
<meta name="description" content="Plataforma de gestión de turnos con chatbot IA de WhatsApp. Para peluquerías, clínicas, gimnasios y más.">
<?php View::partial('partials/head'); ?>
</head>
<body class="bg-white text-ink-900 antialiased">
    <?= View::yield('content') ?>
</body>
</html>
