<?php
/**
 * Script de migración — ejecuta todos los .sql en database/migrations en orden.
 * Uso: composer migrate  |  php scripts/migrate.php
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Dotenv\Dotenv;
use TurneroYa\Core\Config;
use TurneroYa\Core\Database;

if (file_exists(BASE_PATH . '/.env')) {
    Dotenv::createImmutable(BASE_PATH)->load();
}
Config::load(BASE_PATH . '/config');

echo "[TurneroYa] Ejecutando migraciones...\n";

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files);

if (!$files) {
    echo "No hay migraciones\n";
    exit(0);
}

try {
    $pdo = Database::connection();
    foreach ($files as $file) {
        echo "  → " . basename($file) . "... ";
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo "OK\n";
    }
    echo "\n✓ Migraciones ejecutadas correctamente\n";
} catch (\Throwable $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
