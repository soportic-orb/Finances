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
        // Cache-busting per versió: els canvis de CSS/JS s'apliquen després d'actualitzar.
        return $base . '/assets/' . ltrim($path, '/') . '?v=' . rawurlencode(app_version());
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('icon')) {
    /** Icona lineal (estil Tabler) com a SVG inline. */
    function icon(string $name, int $size = 18): string
    {
        static $m = null;
        if ($m === null) {
            $m = [
                'dashboard'  => '<rect x="4" y="4" width="6" height="8" rx="1"/><rect x="4" y="16" width="6" height="4" rx="1"/><rect x="14" y="4" width="6" height="4" rx="1"/><rect x="14" y="12" width="6" height="8" rx="1"/>',
                'coins'      => '<circle cx="12" cy="12" r="9"/><path d="M14.5 9a3.5 4 0 1 0 0 6"/><path d="M8.5 11.5h4"/><path d="M8.5 13h4"/>',
                'bank'       => '<path d="M3 21h18"/><path d="M3 10h18"/><path d="M5 6l7 -3l7 3"/><path d="M4 10v11"/><path d="M20 10v11"/><path d="M8 14v3"/><path d="M12 14v3"/><path d="M16 14v3"/>',
                'wallet'     => '<path d="M17 8V6a2 2 0 0 0 -2 -2H6a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h11a2 2 0 0 0 2 -2v-2"/><path d="M20 12v4h-4a2 2 0 0 1 0 -4h4"/>',
                'transfer'   => '<path d="M3 10h14l-3 -3"/><path d="M21 14H7l3 3"/>',
                'import'     => '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4v12"/>',
                'tags'       => '<path d="M7.5 7.5h.01"/><path d="M3 6v5.2a2 2 0 0 0 .6 1.4l7.4 7.4a2 2 0 0 0 2.8 0l4.6 -4.6a2 2 0 0 0 0 -2.8l-7.4 -7.4A2 2 0 0 0 10.2 3H5a2 2 0 0 0 -2 2z"/>',
                'category'   => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><circle cx="17" cy="17" r="3"/>',
                'adjustments' => '<circle cx="6" cy="9" r="2"/><circle cx="18" cy="15" r="2"/><path d="M6 11v9"/><path d="M6 4v3"/><path d="M18 4v9"/><path d="M18 17v3"/>',
                'target'     => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
                'chart'      => '<path d="M4 19h16"/><path d="M4 15l4 -4l3 3l5 -6"/>',
                'report'     => '<path d="M9 5H7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2V7a2 2 0 0 0 -2 -2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6"/><path d="M9 16h4"/>',
                'robot'      => '<rect x="4" y="8" width="16" height="12" rx="2"/><path d="M12 8V5"/><circle cx="12" cy="4" r="1"/><path d="M9 13v1"/><path d="M15 13v1"/><path d="M9.5 17h5"/>',
                'tool'       => '<path d="M7 10h3V7L6.5 3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8z"/>',
                'users'      => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-1a5 5 0 0 1 5 -5h2a5 5 0 0 1 5 5v1"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/><path d="M21 21v-1a5 5 0 0 0 -4 -4.9"/>',
                'refresh'    => '<path d="M20 11a8 8 0 1 0 -2.5 5.8"/><path d="M20 4v5h-5"/>',
                'user'       => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="3"/><path d="M6.2 18a6 6 0 0 1 11.6 0"/>',
                'settings'   => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2"/><path d="M12 19v2"/><path d="M3 12h2"/><path d="M19 12h2"/><path d="M5.6 5.6l1.4 1.4"/><path d="M17 17l1.4 1.4"/><path d="M5.6 18.4l1.4 -1.4"/><path d="M17 7l1.4 -1.4"/>',
                'logout'     => '<path d="M14 8V6a2 2 0 0 0 -2 -2H5a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M21 12l-3 3"/>',
                'chevron'    => '<path d="M6 9l6 6l6 -6"/>',
                'chat'       => '<path d="M4 21V8a3 3 0 0 1 3 -3h10a3 3 0 0 1 3 3v6a3 3 0 0 1 -3 3H8z"/><path d="M9.5 9h.01"/><path d="M14.5 9h.01"/><path d="M9.5 13a3.5 3.5 0 0 0 5 0"/>',
                'trash'      => '<path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7V4h6v3"/>',
                'maximize'   => '<path d="M4 8V4h4"/><path d="M20 8V4h-4"/><path d="M4 16v4h4"/><path d="M20 16v4h-4"/>',
                'minimize'   => '<path d="M8 4v4H4"/><path d="M16 4v4h4"/><path d="M8 20v-4H4"/><path d="M16 20v-4h4"/>',
                'x'          => '<path d="M6 6l12 12"/><path d="M6 18L18 6"/>',
            ];
        }
        $inner = $m[$name] ?? '';
        return '<svg class="ic" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" '
            . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $inner . '</svg>';
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

if (!function_exists('days_until')) {
    /** Dies sencers fins a una data/hora (negatiu si ja ha passat); null si buida. */
    function days_until(?string $datetime): ?int
    {
        if ($datetime === null || $datetime === '') {
            return null;
        }
        $ts = strtotime($datetime);
        return $ts ? (int) floor(($ts - time()) / 86400) : null;
    }
}

if (!function_exists('app_version')) {
    function app_version(): string
    {
        $file = BASE_PATH . '/VERSION';
        return is_file($file) ? trim((string) file_get_contents($file)) : '0.0.0';
    }
}
