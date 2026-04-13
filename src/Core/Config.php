<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Config loader usando notación punto ("app.timezone").
 */
final class Config
{
    private static array $items = [];

    public static function load(string $dir): void
    {
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if ($name === 'routes') continue;
            self::$items[$name] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = self::$items;
        foreach ($parts as $p) {
            if (!is_array($value) || !array_key_exists($p, $value)) {
                return $default;
            }
            $value = $value[$p];
        }
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $ref = &self::$items;
        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) $ref[$p] = [];
            $ref = &$ref[$p];
        }
        $ref = $value;
    }
}
