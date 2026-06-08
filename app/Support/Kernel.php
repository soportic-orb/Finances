<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * Arrencada de l'aplicació: configuració, sessió segura, capçaleres de
 * seguretat, gestió d'errors i locale.
 */
final class Kernel
{
    public static function boot(): void
    {
        Config::load();

        $debug = (bool) Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');

        date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Madrid'));

        self::startSession();
        // Idioma: preferència de sessió (commutador) o el de configuració.
        $locale = $_SESSION['locale'] ?? (string) Config::get('app.locale', 'ca');
        Lang::setLocale($locale);

        self::securityHeaders();
        self::registerErrorHandlers($debug);
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('FIN_SESSID');
        @session_start();
    }

    private static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        // Assets locals: sense CDN. Es permet inline limitat per a Alpine.
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; "
            . "font-src 'self'; "
            . "connect-src 'self'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'none'"
        );
    }

    private static function registerErrorHandlers(bool $debug): void
    {
        set_exception_handler(static function (Throwable $e) use ($debug): void {
            error_log((string) $e);
            if (!headers_sent()) {
                http_response_code(500);
            }
            if ($debug) {
                echo '<pre>' . e((string) $e) . '</pre>';
                return;
            }
            View::render('errors/500', [], 'layouts/app');
        });

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
