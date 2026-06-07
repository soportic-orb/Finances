<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Autoloader PSR-4 mínim per al namespace App\ → /app.
 *
 * Permet que l'aplicació funcioni sense executar `composer install` (hosting
 * compartit). Quan vendor/ existeix, també es carrega per a les dependències.
 */
final class Autoloader
{
    public static function register(): void
    {
        // Dependències de Composer si estan instal·lades.
        $vendor = BASE_PATH . '/vendor/autoload.php';
        if (is_file($vendor)) {
            require $vendor;
        }

        // Helpers globals.
        require __DIR__ . '/helpers.php';

        spl_autoload_register(static function (string $class): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($path)) {
                require $path;
            }
        });
    }
}
