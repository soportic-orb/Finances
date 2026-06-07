<?php

declare(strict_types=1);

use App\Support\Config;
use App\Support\Csrf;
use App\Support\Lang;

if (!function_exists('e')) {
    /** Escapa per a sortida HTML. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    /** Tradueix una clau i18n. */
    function __(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('asset')) {
    /** URL local d'un asset (sense CDN). */
    function asset(string $path): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_version')) {
    function app_version(): string
    {
        $file = BASE_PATH . '/VERSION';
        return is_file($file) ? trim((string) file_get_contents($file)) : '0.0.0';
    }
}
