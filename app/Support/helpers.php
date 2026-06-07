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

if (!function_exists('money')) {
    /** Formata un import en estil local (1.234,56 €). */
    function money(float|string $amount, string $currency = 'EUR'): string
    {
        return number_format((float) $amount, 2, ',', '.') . ' ' . $currency;
    }
}

if (!function_exists('to_amount')) {
    /**
     * Converteix una entrada de l'usuari a float, tolerant amb formats ca/es i
     * internacional: "1.234,56", "1234.56", "1.234.567,89", "3.500" (→ 3500).
     */
    function to_amount(string $input): float
    {
        $s = trim($input);
        $s = preg_replace('/[^\d,.\-]/', '', $s) ?? '';

        if (str_contains($s, ',') && str_contains($s, '.')) {
            // L'últim separador que apareix és el decimal.
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);      // punts = milers
                $s = str_replace(',', '.', $s);     // coma = decimal
            } else {
                $s = str_replace(',', '', $s);      // comes = milers
            }
        } elseif (str_contains($s, ',')) {
            $s = str_replace(',', '.', $s);         // coma decimal (ca/es)
        } else {
            // Només punts: més d'un → milers; un de sol seguit de 3 dígits → milers.
            $dots = substr_count($s, '.');
            if ($dots > 1 || ($dots === 1 && preg_match('/\.\d{3}$/', $s))) {
                $s = str_replace('.', '', $s);
            }
        }

        return round((float) $s, 2);
    }
}

if (!function_exists('uuid4')) {
    function uuid4(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}

if (!function_exists('app_version')) {
    function app_version(): string
    {
        $file = BASE_PATH . '/VERSION';
        return is_file($file) ? trim((string) file_get_contents($file)) : '0.0.0';
    }
}
