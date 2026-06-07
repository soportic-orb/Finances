<?php

declare(strict_types=1);

/**
 * CLI d'actualització OTA (per a cron o execució manual segura).
 *
 *   php bin/update.php            → comprova i actualitza si n'hi ha
 *   php bin/update.php --check    → només comprova
 *   php bin/update.php --force    → actualitza encara que no detecti versió nova
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Només per CLI.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Support/Autoloader.php';

use App\Services\UpdateService;
use App\Support\Autoloader;
use App\Support\Config;

Autoloader::register();
Config::load();

if (!Config::isInstalled()) {
    fwrite(STDERR, "L'aplicació no està instal·lada.\n");
    exit(1);
}

$svc = new UpdateService();
$check = in_array('--check', $argv, true);
$force = in_array('--force', $argv, true);

try {
    $info = $svc->checkForUpdate();
    echo "Versió actual: {$info['current']} · remota: {$info['remote']} · branca: {$svc->branch()}\n";

    if ($check) {
        echo $info['available'] ? "Hi ha una actualització disponible.\n" : "Ja estàs al dia.\n";
        exit(0);
    }
    if (!$info['available'] && !$force) {
        echo "Cap actualització pendent.\n";
        exit(0);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error en comprovar: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Actualitzant…\n";
$result = $svc->run();
echo $result['message'] . "\n";
exit($result['ok'] ? 0 : 1);
