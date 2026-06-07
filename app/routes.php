<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Support\Router;

/**
 * Definició de rutes. Es retorna un callable que rep el Router.
 */
return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HomeController::class, 'health']);
};
