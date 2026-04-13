<?php
/**
 * TurneroYa - Front Controller
 *
 * Todas las requests entran por aquí.
 */
declare(strict_types=1);

define('TURNEROYA_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use TurneroYa\Core\App;

try {
    $app = new App(BASE_PATH);
    $app->boot();
    $app->run();
} catch (\Throwable $e) {
    http_response_code(500);
    $debug = ($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG')) === 'true';
    if ($debug) {
        echo '<pre style="background:#1e293b;color:#f8fafc;padding:20px;font-family:monospace;">';
        echo "ERROR: " . htmlspecialchars($e->getMessage()) . "\n\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
    } else {
        echo '<h1>500 - Error interno del servidor</h1>';
    }
}
