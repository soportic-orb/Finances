<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Config;
use App\Support\Migrator;
use App\Support\Shell;

/**
 * Actualitzacions OTA via Git, segures per a hosting compartit.
 *
 * Flux: mode manteniment → backup BD + /config → git fetch + reset --hard →
 * composer install --no-dev → migracions → treu manteniment. Rollback automàtic
 * (torna al commit anterior i restaura backups) si qualsevol pas falla.
 * Mai esborra /config/keys.
 */
final class UpdateService
{
    private string $root;
    private string $branch;

    public function __construct(?string $root = null, ?string $branch = null)
    {
        $this->root = $root ?? BASE_PATH;
        $this->branch = $branch ?? (string) (Config::get('app.update_branch') ?: $this->currentBranch() ?: 'main');
    }

    // ---- Mode manteniment ----

    public function maintenanceFile(): string
    {
        return $this->root . '/storage/maintenance.flag';
    }

    public function isMaintenance(): bool
    {
        return is_file($this->maintenanceFile());
    }

    public function enableMaintenance(): void
    {
        @file_put_contents($this->maintenanceFile(), date('c'));
    }

    public function disableMaintenance(): void
    {
        @unlink($this->maintenanceFile());
    }

    // ---- Versions / git ----

    public function localVersion(): string
    {
        $f = $this->root . '/VERSION';
        return is_file($f) ? trim((string) file_get_contents($f)) : '0.0.0';
    }

    public function branch(): string
    {
        return $this->branch;
    }

    private function currentBranch(): string
    {
        $r = Shell::run(['git', '-C', $this->root, 'rev-parse', '--abbrev-ref', 'HEAD']);
        return $r['code'] === 0 ? trim($r['output']) : '';
    }

    private function headCommit(): string
    {
        $r = Shell::run(['git', '-C', $this->root, 'rev-parse', 'HEAD']);
        return $r['code'] === 0 ? trim($r['output']) : '';
    }

    /**
     * Comprova si hi ha actualització disponible (fa fetch).
     * @return array{current:string,remote:string,available:bool}
     */
    public function checkForUpdate(): array
    {
        $this->git(['fetch', '--all', '--quiet']);
        $r = Shell::run(['git', '-C', $this->root, 'show', 'origin/' . $this->branch . ':VERSION']);
        $remote = $r['code'] === 0 ? trim($r['output']) : $this->localVersion();
        $current = $this->localVersion();
        return [
            'current'   => $current,
            'remote'    => $remote,
            'available' => version_compare($remote, $current, '>'),
        ];
    }

    /** @param array<int,string> $args */
    private function git(array $args): array
    {
        return Shell::run(array_merge(['git', '-C', $this->root], $args));
    }

    private function log(string $msg): void
    {
        $line = '[' . date('c') . '] ' . $msg . "\n";
        @file_put_contents($this->root . '/storage/logs/update.log', $line, FILE_APPEND);
    }

    /** @return array<int,array{ts:string,line:string}> darreres línies del log */
    public function recentLog(int $lines = 30): array
    {
        $f = $this->root . '/storage/logs/update.log';
        if (!is_file($f)) {
            return [];
        }
        $all = array_filter(explode("\n", (string) file_get_contents($f)));
        return array_map(static fn ($l) => ['ts' => '', 'line' => $l], array_slice($all, -$lines));
    }

    /**
     * Executa l'actualització amb rollback automàtic.
     *
     * @param array{skip_composer?:bool,skip_db_backup?:bool,skip_config_backup?:bool,migrate?:callable} $opts
     * @return array{ok:bool,from:string,to:string,message:string}
     */
    public function run(array $opts = []): array
    {
        $from = $this->localVersion();
        $this->log("=== Actualització iniciada (branca {$this->branch}, versió $from) ===");

        if (!Shell::available('git')) {
            return ['ok' => false, 'from' => $from, 'to' => $from, 'message' => 'git no disponible'];
        }

        $prevCommit = $this->headCommit();
        if ($prevCommit === '') {
            return ['ok' => false, 'from' => $from, 'to' => $from, 'message' => 'No és un repositori git'];
        }

        $this->enableMaintenance();
        $backup = new BackupService($this->root);
        $dbBackup = null;
        $cfgBackup = null;

        try {
            // 1) Backups previs.
            if (empty($opts['skip_db_backup'])) {
                $dbBackup = $backup->backupDatabase();
                $this->log("Backup BD: " . basename($dbBackup));
            }
            if (empty($opts['skip_config_backup'])) {
                $cfgBackup = $backup->backupConfig($this->root);
                if ($cfgBackup) {
                    $this->log("Backup config: " . basename($cfgBackup));
                }
            }

            // 2) git fetch + reset.
            $f = $this->git(['fetch', '--all', '--quiet']);
            if ($f['code'] !== 0) {
                throw new \RuntimeException('git fetch ha fallat: ' . $f['output']);
            }
            $reset = $this->git(['reset', '--hard', 'origin/' . $this->branch]);
            if ($reset['code'] !== 0) {
                throw new \RuntimeException('git reset ha fallat: ' . $reset['output']);
            }
            $this->log('git reset --hard origin/' . $this->branch . ' OK');

            // 3) composer install --no-dev (si escau).
            if (empty($opts['skip_composer']) && is_file($this->root . '/composer.json') && Shell::available('composer')) {
                $c = Shell::run(['composer', 'install', '--no-dev', '--no-interaction', '--optimize-autoloader'], $this->root);
                if ($c['code'] !== 0) {
                    throw new \RuntimeException('composer install ha fallat: ' . $c['output']);
                }
                $this->log('composer install --no-dev OK');
            }

            // 4) Migracions pendents.
            $migrate = $opts['migrate'] ?? static fn () => (new Migrator())->migrate();
            $done = $migrate();
            $this->log('Migracions aplicades: ' . (is_array($done) ? count($done) : 0));

            // 5) Final.
            $to = $this->localVersion();
            $this->disableMaintenance();
            $backup->prune(10);
            $this->log("=== Actualització OK: $from → $to ===");
            return ['ok' => true, 'from' => $from, 'to' => $to, 'message' => "Actualitzat a $to"];
        } catch (\Throwable $e) {
            // Rollback.
            $this->log('ERROR: ' . $e->getMessage() . ' — iniciant rollback');
            $this->git(['reset', '--hard', $prevCommit]);
            if ($dbBackup !== null) {
                try {
                    $backup->restoreDatabase($dbBackup);
                    $this->log('BD restaurada des de ' . basename($dbBackup));
                } catch (\Throwable $re) {
                    $this->log('AVÍS: restauració de BD ha fallat: ' . $re->getMessage());
                }
            }
            if ($cfgBackup !== null) {
                $backup->restoreConfig($cfgBackup, $this->root);
            }
            $this->disableMaintenance();
            $this->log('Rollback complet. Instància a la versió ' . $this->localVersion());
            return ['ok' => false, 'from' => $from, 'to' => $this->localVersion(), 'message' => 'Error: ' . $e->getMessage() . ' (rollback aplicat)'];
        }
    }
}
