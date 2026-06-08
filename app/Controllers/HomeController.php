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

    /** Canvia l'idioma de la sessió i torna a la pàgina anterior. @param array<string,string> $params */
    public function locale(array $params): void
    {
        $lang = $params['lang'] ?? 'ca';
        if (in_array($lang, ['ca', 'es'], true)) {
            $_SESSION['locale'] = $lang;
        }
        $back = parse_url($_SERVER['HTTP_REFERER'] ?? '/', PHP_URL_PATH);
        $back = is_string($back) && str_starts_with($back, '/') ? $back : '/';
        header('Location: ' . $back);
        exit;
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
