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
        $this->assertProductionConfig();

        // 3) Timezone
        date_default_timezone_set((string) Config::get('app.timezone', 'America/Argentina/Buenos_Aires'));

        // 4) Errores
        $debug = (bool) Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        // En contenedor (Render/Docker) mandamos a stderr para que aparezca en los logs del PaaS;
        // si no, al archivo de la app.
        $inContainer = getenv('RENDER') !== false || getenv('DOCKER') !== false || file_exists('/.dockerenv');
        ini_set('error_log', $inContainer ? 'php://stderr' : $this->basePath . '/storage/logs/php-error.log');

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

    private function assertProductionConfig(): void
    {
        if (PHP_SAPI === 'cli' || Config::get('app.env') !== 'production') {
            return;
        }

        // Mínimos no negociables para que la app levante. El resto (Anthropic,
        // Twilio, MercadoPago, Mail) se validan cuando cada servicio se usa,
        // así el modo mínimo puede correr landing/auth sin todos los tokens.
        $required = ['APP_KEY', 'APP_URL'];

        $missing = [];
        foreach ($required as $key) {
            $systemValue = getenv($key);
            $value = (string) ($systemValue !== false ? $systemValue : ($_ENV[$key] ?? ''));
            if ($value === '' || str_contains($value, 'cambiar-en-produccion')) {
                $missing[] = $key;
            }
        }

        $systemAppUrl = getenv('APP_URL');
        $appUrl = (string) ($systemAppUrl !== false ? $systemAppUrl : ($_ENV['APP_URL'] ?? ''));
        if ($appUrl !== '' && (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1'))) {
            $missing[] = 'APP_URL publico';
        }

        if (Config::get('app.debug') !== false) {
            $missing[] = 'APP_DEBUG=false';
        }

        if ($missing !== []) {
            throw new \RuntimeException('Configuracion de produccion incompleta: ' . implode(', ', $missing));
        }
    }
}
