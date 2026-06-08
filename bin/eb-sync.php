<?php

declare(strict_types=1);

/**
 * Cron de sincronització d'Enable Banking.
 *
 *   php bin/eb-sync.php
 *
 * Recorre tots els enllaços de compte actius, sincronitza i avisa de
 * consentiments a punt de caducar. No registra dades sensibles.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Només per CLI.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Support/Autoloader.php';

use App\Models\EbAccountLink;
use App\Services\EbSyncService;
use App\Services\EnableBankingService;
use App\Support\Autoloader;
use App\Support\Config;

Autoloader::register();
Config::load();

if (!Config::isInstalled()) {
    fwrite(STDERR, "L'aplicació no està instal·lada.\n");
    exit(1);
}

try {
    $links = EbAccountLink::allActive();
} catch (\Throwable $e) {
    fwrite(STDERR, 'No s\'ha pogut accedir a la base de dades: ' . $e->getMessage() . "\n");
    exit(1);
}

if ($links === []) {
    echo "Cap enllaç actiu.\n";
    exit(0);
}

$totalNew = 0;
$totalDup = 0;
$errors = 0;

foreach ($links as $link) {
    $hid = (int) $link['household_id'];
    $name = (string) $link['account_name'];

    // Avís de consentiment proper a caducar.
    if (!empty($link['valid_until'])) {
        $days = (int) floor((strtotime((string) $link['valid_until']) - time()) / 86400);
        if ($days < 0) {
            echo "[AVÍS] Consentiment caducat per «$name»; cal reautoritzar.\n";
            continue;
        }
        if ($days <= 7) {
            echo "[AVÍS] Consentiment de «$name» caduca en $days dies.\n";
        }
    }

    try {
        $eb = new EnableBankingService($hid);
        if (!$eb->isConfigured()) {
            echo "[OMÈS] «$name»: Enable Banking no configurat per a la llar $hid.\n";
            continue;
        }
        [$new, $dup] = (new EbSyncService($eb))->syncLink($link);
        $totalNew += $new;
        $totalDup += $dup;
        echo "[OK] «$name»: $new nous, $dup duplicats.\n";
    } catch (\Throwable $e) {
        $errors++;
        echo "[ERROR] «$name»: " . $e->getMessage() . "\n";
    }
}

echo "---\nTotal: $totalNew nous, $totalDup duplicats, $errors errors.\n";
exit($errors > 0 ? 1 : 0);
