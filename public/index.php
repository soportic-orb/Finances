<?php

declare(strict_types=1);

/**
 * Front controller. Únic punt d'entrada (document root = /public).
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';

use App\Support\Autoloader;
use App\Support\Csrf;
use App\Support\Kernel;
use App\Support\Router;

Autoloader::register();
Kernel::boot();

// Si encara no s'ha executat l'instal·lador, redirigeix-hi.
if (!\App\Support\Config::isInstalled()) {
    header('Location: ' . url('/install/'));
    exit;
}

// Mode manteniment (durant actualitzacions OTA): bloqueja excepte /update i /assets.
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (
    is_file(BASE_PATH . '/storage/maintenance.flag')
    && !str_starts_with($reqPath, '/update')
    && !str_starts_with($reqPath, '/assets')
) {
    http_response_code(503);
    header('Retry-After: 120');
    \App\Support\View::render('errors/maintenance', [], 'layouts/auth');
    exit;
}

// Protecció CSRF per a peticions que modifiquen estat.
Csrf::check();

$router = new Router();
(require BASE_PATH . '/app/routes.php')($router);
$router->dispatch();
