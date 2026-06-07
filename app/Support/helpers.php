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

if (!function_exists('redirect')) {
    /** Redirigeix a una ruta interna i atura l'execució. */
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('abort')) {
    /** Atura amb un codi HTTP i una pàgina d'error. */
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        \App\Support\View::render('errors/generic', [
            'code'    => $code,
            'message' => $message,
        ], 'layouts/app');
        exit;
    }
}

if (!function_exists('old')) {
    /** Recupera un valor d'entrada anterior (flash de formulari). */
    function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['_old'][$key] ?? $default);
    }
}

if (!function_exists('flash')) {
    /** Desa o llegeix un missatge flash de sessió. */
    function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }
}

if (!function_exists('app_version')) {
    function app_version(): string
    {
        $file = BASE_PATH . '/VERSION';
        return is_file($file) ? trim((string) file_get_contents($file)) : '0.0.0';
    }
}
