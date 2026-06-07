<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Services\BackupService;
use App\Services\MigrationService;
use App\Services\UpdateService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

/**
 * Actualitzacions OTA i backups. Només el propietari de la llar.
 */
final class UpdateController
{
    public function index(): void
    {
        Guard::requireOwner();
        $svc = new UpdateService();
        $backup = new BackupService();

        View::render('update/index', [
            'version'     => $svc->localVersion(),
            'branch'      => $svc->branch(),
            'maintenance' => $svc->isMaintenance(),
            'check'       => flash('upd_check'),
            'log'         => $svc->recentLog(20),
            'backups'     => $backup->list(),
            'ok'          => flash('upd_ok'),
            'error'       => flash('upd_error'),
        ], 'layouts/app');
    }

    public function check(): void
    {
        Guard::requireOwner();
        try {
            $r = (new UpdateService())->checkForUpdate();
            $msg = $r['available']
                ? __('upd.available', ['from' => $r['current'], 'to' => $r['remote']])
                : __('upd.uptodate', ['v' => $r['current']]);
            flash('upd_check', $msg);
        } catch (\Throwable $e) {
            flash('upd_error', $e->getMessage());
        }
        redirect('/update');
    }

    public function run(): void
    {
        Guard::requireOwner();
        $result = (new UpdateService())->run();
        AuditLog::record('ota_update', 'system', null, $result['from'] . '→' . $result['to'] . ' ' . ($result['ok'] ? 'ok' : 'rollback'));
        flash($result['ok'] ? 'upd_ok' : 'upd_error', $result['message']);
        redirect('/update');
    }

    /** Exporta un paquet de migració xifrat i el descarrega. */
    public function export(): void
    {
        Guard::requireOwner();
        $pass = (string) ($_POST['passphrase'] ?? '');
        try {
            $file = (new MigrationService())->export($pass);
            AuditLog::record('migration_export', 'system', null, basename($file));
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } catch (\Throwable $e) {
            flash('upd_error', $e->getMessage());
            redirect('/update');
        }
    }

    /** Importa un paquet de migració sobre la instància actual. */
    public function import(): void
    {
        Guard::requireOwner();
        $pass = (string) ($_POST['passphrase'] ?? '');
        if (empty($_FILES['bundle']['tmp_name']) || !is_uploaded_file($_FILES['bundle']['tmp_name'])) {
            flash('upd_error', __('mig.no_file'));
            redirect('/update');
        }
        $tmp = sys_get_temp_dir() . '/upload_' . bin2hex(random_bytes(6)) . '.fin';
        move_uploaded_file($_FILES['bundle']['tmp_name'], $tmp);
        try {
            $r = (new MigrationService())->import($tmp, $pass);
            AuditLog::record('migration_import', 'system', null, 'v' . $r['version']);
            flash('upd_ok', __('mig.import_done', ['v' => $r['version']]));
        } catch (\Throwable $e) {
            flash('upd_error', $e->getMessage());
        } finally {
            @unlink($tmp);
        }
        redirect('/update');
    }

    public function backup(): void
    {
        Guard::requireOwner();
        try {
            $b = new BackupService();
            $db = $b->backupDatabase();
            $cfg = $b->backupConfig();
            $b->prune(10);
            AuditLog::record('backup_manual', 'system', null, basename($db));
            flash('upd_ok', __('upd.backup_done'));
        } catch (\Throwable $e) {
            flash('upd_error', $e->getMessage());
        }
        redirect('/update');
    }
}
