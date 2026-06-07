<?php

declare(strict_types=1);

/**
 * Punt d'entrada web de l'instal·lador (sota el document root /public).
 * La lògica viu aïllada a /install/Installer.php; aquí només es despatxa el flux.
 */

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/app/Support/Autoloader.php';
require BASE_PATH . '/install/Installer.php';

use App\Support\Autoloader;
use App\Support\Config;
use App\Support\Csrf;

Autoloader::register();
Config::load();
session_name('FIN_INSTALL');
session_start();

$installer = new Installer();

/** Renderitza una vista de l'instal·lador dins del layout. */
function render(string $view, array $data, int $step): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/install/views/' . $view . '.php';
    $body = (string) ob_get_clean();
    $total = 4;
    require BASE_PATH . '/install/views/layout.php';
    exit;
}

// Si ja està instal·lat, bloqueja.
if ($installer->isLocked()) {
    render('locked', [], 4);
}

$step = (int) ($_GET['step'] ?? 1);
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

// Verificació CSRF per a passos amb formulari.
if ($isPost && !Csrf::verify($_POST['_token'] ?? null)) {
    http_response_code(419);
    exit('Token CSRF invàlid o caducat.');
}

switch ($step) {
    case 1:
        render('requirements', [
            'checks' => $installer->requirements(),
            'pass'   => $installer->requirementsPass(),
        ], 1);
        break;

    case 2:
        if ($isPost) {
            $db = [
                'host' => trim($_POST['host'] ?? ''),
                'port' => trim($_POST['port'] ?? '3306'),
                'name' => trim($_POST['name'] ?? ''),
                'user' => trim($_POST['user'] ?? ''),
                'pass' => (string) ($_POST['pass'] ?? ''),
            ];
            try {
                $installer->testDatabase($db);
                $_SESSION['install_db'] = $db;
                header('Location: ?step=3');
                exit;
            } catch (\Throwable $e) {
                render('database', [
                    'csrf'  => Csrf::field(),
                    'old'   => $db,
                    'error' => 'No s\'ha pogut connectar: ' . $e->getMessage(),
                ], 2);
            }
        }
        render('database', [
            'csrf'  => Csrf::field(),
            'old'   => $_SESSION['install_db'] ?? [],
            'error' => null,
        ], 2);
        break;

    case 3:
        if (empty($_SESSION['install_db'])) {
            header('Location: ?step=2');
            exit;
        }
        if ($isPost) {
            $app = [
                'household_name' => trim($_POST['household_name'] ?? ''),
                'currency'       => trim($_POST['currency'] ?? 'EUR'),
                'locale'         => trim($_POST['locale'] ?? 'ca'),
                'timezone'       => trim($_POST['timezone'] ?? 'Europe/Madrid'),
                'url'            => trim($_POST['url'] ?? ''),
            ];
            $owner = [
                'name'      => trim($_POST['owner_name'] ?? ''),
                'email'     => trim($_POST['owner_email'] ?? ''),
                'password'  => (string) ($_POST['owner_password'] ?? ''),
                'password2' => (string) ($_POST['owner_password2'] ?? ''),
            ];

            $error = validateSetup($app, $owner);
            if ($error === null) {
                try {
                    $installer->install([
                        'db'    => $_SESSION['install_db'],
                        'app'   => $app,
                        'owner' => $owner,
                    ]);
                    unset($_SESSION['install_db']);
                    render('done', [
                        'appUrl' => rtrim($app['url'], '/') . '/',
                    ], 4);
                } catch (\Throwable $e) {
                    $error = 'Error durant la instal·lació: ' . $e->getMessage();
                }
            }

            render('setup', [
                'csrf'  => Csrf::field(),
                'old'   => array_merge($app, [
                    'owner_name'  => $owner['name'],
                    'owner_email' => $owner['email'],
                ]),
                'error' => $error,
            ], 3);
        }
        render('setup', [
            'csrf'  => Csrf::field(),
            'old'   => [],
            'error' => null,
        ], 3);
        break;

    case 'import':
        if (empty($_SESSION['install_db'])) {
            header('Location: ?step=2');
            exit;
        }
        if ($isPost) {
            $pass = (string) ($_POST['passphrase'] ?? '');
            if (empty($_FILES['bundle']['tmp_name']) || !is_uploaded_file($_FILES['bundle']['tmp_name'])) {
                render('setup', ['csrf' => Csrf::field(), 'old' => [], 'error' => 'Cal seleccionar un paquet .fin.'], 3);
            }
            $tmpFile = sys_get_temp_dir() . '/instimport_' . bin2hex(random_bytes(6)) . '.fin';
            move_uploaded_file($_FILES['bundle']['tmp_name'], $tmpFile);
            try {
                $installer->importMigration($_SESSION['install_db'], $tmpFile, $pass);
                unset($_SESSION['install_db']);
                @unlink($tmpFile);
                render('done', ['appUrl' => '/'], 4);
            } catch (\Throwable $e) {
                @unlink($tmpFile);
                render('setup', [
                    'csrf'  => Csrf::field(),
                    'old'   => [],
                    'error' => 'Error en importar: ' . $e->getMessage(),
                ], 3);
            }
        }
        header('Location: ?step=3');
        exit;

    default:
        header('Location: ?step=1');
        exit;
}

/**
 * @param array<string,string> $app
 * @param array<string,string> $owner
 */
function validateSetup(array $app, array $owner): ?string
{
    if ($app['household_name'] === '') {
        return 'El nom de la llar és obligatori.';
    }
    if (!in_array($app['locale'], ['ca', 'es'], true)) {
        return 'Idioma no vàlid.';
    }
    if (@timezone_open($app['timezone']) === false) {
        return 'Zona horària no vàlida.';
    }
    if ($owner['name'] === '') {
        return 'El nom del propietari és obligatori.';
    }
    if (!filter_var($owner['email'], FILTER_VALIDATE_EMAIL)) {
        return 'El correu del propietari no és vàlid.';
    }
    if (mb_strlen($owner['password']) < 10) {
        return 'La contrasenya ha de tenir com a mínim 10 caràcters.';
    }
    if ($owner['password'] !== $owner['password2']) {
        return 'Les contrasenyes no coincideixen.';
    }
    return null;
}
