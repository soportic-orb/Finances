<?php

/**
 * Plantilla de configuració.
 *
 * L'instal·lador web (Fase 2) generarà /config/config.php a partir d'aquesta
 * plantilla amb les dades reals i una APP_KEY generada aleatòriament.
 *
 * /config/config.php està exclòs de Git (.gitignore). Aquest fitxer d'exemple
 * serveix també de fallback en desenvolupament perquè la bastida respongui.
 */

declare(strict_types=1);

return [
    'app' => [
        // Clau d'aplicació (base64 de 32 bytes) per a xifratge AES-256-GCM.
        // En producció la genera l'instal·lador. Aquí, valor de desenvolupament.
        'key'      => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        'env'      => 'production',        // production | sandbox
        'debug'    => false,
        'url'      => '',                  // p. ex. https://finances.example.com
        'locale'   => 'ca',               // ca | es
        'timezone' => 'Europe/Madrid',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'finances',
        'user'    => 'finances',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
];
