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

    private function assertProductionConfig(): void
    {
        if (PHP_SAPI === 'cli' || Config::get('app.env') !== 'production') {
            return;
        }

        $required = [
            'APP_KEY',
            'APP_URL',
            'CRON_SECRET',
            'ANTHROPIC_API_KEY',
            'TWILIO_ACCOUNT_SID',
            'TWILIO_AUTH_TOKEN',
            'TWILIO_WHATSAPP_FROM',
            'MERCADOPAGO_ACCESS_TOKEN',
            'MERCADOPAGO_PUBLIC_KEY',
            'MERCADOPAGO_WEBHOOK_SECRET',
        ];

        $missing = [];
        foreach ($required as $key) {
            $systemValue = getenv($key);
            $value = (string) ($systemValue !== false ? $systemValue : ($_ENV[$key] ?? ''));
            if ($value === '' || str_contains($value, 'xxxx') || str_contains($value, 'cambiar-en-produccion') || str_starts_with($value, 'TEST-')) {
                $missing[] = $key;
            }
        }

        $systemAppUrl = getenv('APP_URL');
        $appUrl = (string) ($systemAppUrl !== false ? $systemAppUrl : ($_ENV['APP_URL'] ?? ''));
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
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
