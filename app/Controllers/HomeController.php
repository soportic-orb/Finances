<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\Auth;

final class HomeController
{
    /** Arrel: redirigeix al tauler o al login segons la sessió. */
    public function index(): void
    {
        redirect(Auth::check() ? '/dashboard' : '/login');
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
