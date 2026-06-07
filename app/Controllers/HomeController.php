<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Config;
use App\Support\View;

final class HomeController
{
    /** Ruta de prova de la bastida. */
    public function index(): void
    {
        View::render('home', [
            'version'   => app_version(),
            'installed' => Config::isInstalled(),
        ], 'layout');
    }

    /** Endpoint de salut (JSON), útil per a monitoratge i cron. */
    public function health(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'ok',
            'version' => app_version(),
            'time'    => date('c'),
        ], JSON_UNESCAPED_UNICODE);
    }
}
