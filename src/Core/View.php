<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Render de vistas PHP puras con layouts y secciones.
 * Uso: View::render('dashboard/home', ['user' => $user])
 */
final class View
{
    private static array $sections = [];
    private static array $sectionStack = [];
    private static ?string $extends = null;

    public static function render(string $template, array $data = []): string
    {
        self::$sections = [];
        self::$sectionStack = [];
        self::$extends = null;

        extract($data, EXTR_SKIP);
        ob_start();
        $path = BASE_PATH . '/src/Views/' . str_replace('.', '/', $template) . '.php';
        if (!file_exists($path)) {
            ob_end_clean();
            throw new \RuntimeException("Vista no encontrada: $template ($path)");
        }
        include $path;
        $content = (string) ob_get_clean();

        if (self::$extends !== null) {
            // Solo setear 'content' si el template NO definió una sección 'content' propia
            // (el contenido fuera de secciones va como content por defecto)
            if (!isset(self::$sections['content'])) {
                self::$sections['content'] = $content;
            }
            $layoutPath = BASE_PATH . '/src/Views/' . str_replace('.', '/', self::$extends) . '.php';
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException('Layout no encontrado: ' . self::$extends);
            }
            extract($data, EXTR_SKIP);
            ob_start();
            include $layoutPath;
            $content = (string) ob_get_clean();
        }
        return $content;
    }

    public static function extend(string $layout): void
    {
        self::$extends = $layout;
    }

    public static function section(string $name): void
    {
        self::$sectionStack[] = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        $name = array_pop(self::$sectionStack);
        if ($name === null) return;
        self::$sections[$name] = (string) ob_get_clean();
    }

    public static function yield(string $name, string $default = ''): void
    {
        echo self::$sections[$name] ?? $default;
    }

    public static function partial(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $path = BASE_PATH . '/src/Views/' . str_replace('.', '/', $template) . '.php';
        if (file_exists($path)) include $path;
    }
}
