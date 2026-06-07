<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Config;
use App\Support\DB;
use PDO;

/**
 * Còpies de seguretat de la base de dades i de /config (inclou /config/keys).
 *
 * El bolcat de BD és en PHP pur (PDO) per funcionar en hosting compartit sense
 * `mysqldump`. La còpia de /config usa ZipArchive.
 */
final class BackupService
{
    private string $backupDir;

    public function __construct(?string $root = null)
    {
        $root ??= BASE_PATH;
        $this->backupDir = $root . '/storage/backups';
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0750, true);
        }
    }

    public function dir(): string
    {
        return $this->backupDir;
    }

    /** Bolca tota la BD a un fitxer .sql i retorna la ruta. */
    public function backupDatabase(): string
    {
        $pdo = DB::connection();
        $file = $this->backupDir . '/db-' . date('Ymd-His') . '.sql';
        $fh = fopen($file, 'w');
        if ($fh === false) {
            throw new \RuntimeException('No s\'ha pogut crear el fitxer de backup de BD.');
        }

        fwrite($fh, "-- Backup " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\n");
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? ($create['Create View'] ?? '');
            fwrite($fh, "\nDROP TABLE IF EXISTS `$table`;\n$createSql;\n");

            $rows = $pdo->query('SELECT * FROM `' . $table . '`');
            foreach ($rows as $row) {
                $cols = array_map(static fn ($c) => '`' . $c . '`', array_keys($row));
                $vals = array_map(static function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                fwrite($fh, 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n");
            }
        }
        fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
        return $file;
    }

    /** Restaura la BD a partir d'un fitxer .sql. */
    public function restoreDatabase(string $file): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Backup de BD no trobat: ' . $file);
        }
        $sql = (string) file_get_contents($file);
        DB::connection()->exec($sql);
    }

    /** Còpia de /config (inclou config.php i /config/keys) en un ZIP. Retorna ruta o null. */
    public function backupConfig(?string $root = null): ?string
    {
        $root ??= BASE_PATH;
        $configDir = $root . '/config';
        if (!is_dir($configDir) || !class_exists(\ZipArchive::class)) {
            return null;
        }
        $file = $this->backupDir . '/config-' . date('Ymd-His') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($configDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            if ($f->isFile()) {
                $local = 'config/' . substr($f->getPathname(), strlen($configDir) + 1);
                $zip->addFile($f->getPathname(), $local);
            }
        }
        $zip->close();
        return $file;
    }

    /** Restaura /config des d'un ZIP. */
    public function restoreConfig(string $zipFile, ?string $root = null): void
    {
        $root ??= BASE_PATH;
        if (!is_file($zipFile) || !class_exists(\ZipArchive::class)) {
            return;
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($root);
            $zip->close();
            // Reassegura permisos de les claus.
            foreach (glob($root . '/config/keys/*.pem') ?: [] as $pem) {
                @chmod($pem, 0600);
            }
        }
    }

    /** @return array<int,array{file:string,size:int,time:int}> */
    public function list(): array
    {
        $out = [];
        foreach (glob($this->backupDir . '/*') ?: [] as $f) {
            if (is_file($f) && basename($f) !== '.gitkeep') {
                $out[] = ['file' => basename($f), 'size' => (int) filesize($f), 'time' => (int) filemtime($f)];
            }
        }
        usort($out, static fn ($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** Conserva només els N backups més recents de cada tipus. */
    public function prune(int $keep = 10): void
    {
        foreach (['db-*.sql', 'config-*.zip'] as $pattern) {
            $files = glob($this->backupDir . '/' . $pattern) ?: [];
            usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
            foreach (array_slice($files, $keep) as $old) {
                @unlink($old);
            }
        }
    }
}
