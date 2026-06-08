<?php

declare(strict_types=1);

/**
 * CLI de migracions.
 *
 *   php bin/migrate.php            → aplica les migracions pendents
 *   php bin/migrate.php --status   → mostra l'estat sense aplicar res
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Aquest script només s'executa per CLI.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Support/Autoloader.php';

use App\Support\Autoloader;
use App\Support\Config;
use App\Support\Migrator;

Autoloader::register();
Config::load();

$migrator = new Migrator();

try {
    if (in_array('--status', $argv, true)) {
        echo "Estat de les migracions:\n";
        foreach ($migrator->status() as $row) {
            $mark = $row['applied'] ? '[✓]' : '[ ]';
            echo "  $mark {$row['version']}\n";
        }
        exit(0);
    }

    $done = $migrator->migrate();
    if ($done === []) {
        echo "Cap migració pendent. La base de dades està al dia.\n";
    } else {
        echo "Migracions aplicades:\n";
        foreach ($done as $version) {
            echo "  + $version\n";
        }
    }
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
