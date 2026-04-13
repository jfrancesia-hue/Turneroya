<?php
declare(strict_types=1);

namespace TurneroYa\Core;

use Dotenv\Dotenv;

/**
 * Contenedor principal de la aplicación TurneroYa.
 * Hace bootstrap del entorno, config, sesión, DB y router.
 */
final class App
{
    private static ?self $instance = null;
    private Router $router;

    public function __construct(public readonly string $basePath)
    {
        self::$instance = $this;
    }

    public static function getInstance(): self
    {
        return self::$instance ?? throw new \RuntimeException('App no inicializada');
    }

    public function boot(): void
    {
        // 1) Cargar .env
        if (file_exists($this->basePath . '/.env')) {
            Dotenv::createImmutable($this->basePath)->load();
        }

        // 2) Cargar config
        Config::load($this->basePath . '/config');

        // 3) Timezone
        date_default_timezone_set((string) Config::get('app.timezone', 'America/Argentina/Buenos_Aires'));

        // 4) Errores
        $debug = (bool) Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $this->basePath . '/storage/logs/php-error.log');

        // 5) Sesión
        Session::start();

        // 6) Router
        $this->router = new Router();
        $this->loadRoutes();
    }

    public function run(): void
    {
        $this->router->dispatch();
    }

    public function router(): Router
    {
        return $this->router;
    }

    private function loadRoutes(): void
    {
        $routes = require $this->basePath . '/config/routes.php';
        $routes($this->router);
    }
}
