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

// Protecció CSRF per a peticions que modifiquen estat.
Csrf::check();

$router = new Router();
(require BASE_PATH . '/app/routes.php')($router);
$router->dispatch();
