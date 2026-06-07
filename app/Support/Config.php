<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Càrrega de configuració des de /config/config.php amb fallback a la plantilla
 * d'exemple. Accés per notació de punt: Config::get('db.host').
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        $file = BASE_PATH . '/config/config.php';
        if (!is_file($file)) {
            $file = BASE_PATH . '/config/config.example.php';
        }
        self::$items = require $file;
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }
        $segments = explode('.', $key);
        $value = self::$items;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__MISSING__') !== '__MISSING__';
    }

    /** Indica si existeix la configuració real (no la plantilla). */
    public static function isInstalled(): bool
    {
        return is_file(BASE_PATH . '/config/config.php');
    }
}
