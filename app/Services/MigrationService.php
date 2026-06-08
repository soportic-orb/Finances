<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Config;
use App\Support\Crypto;
use App\Support\DB;

/**
 * Eina de migració de servidor: exporta la instància (BD + claus .pem + APP_KEY)
 * en un paquet ZIP xifrat amb passphrase, amb manifest i checksums; i la
 * reimporta en un altre servidor (o el mateix).
 *
 * El paquet inclou /config/keys i l'APP_KEY (al manifest) perquè els secrets
 * xifrats (api_credentials) segueixin sent desxifrables a destí.
 */
final class MigrationService
{
    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? BASE_PATH;
    }

    /**
     * Crea un paquet de migració xifrat. Retorna la ruta del fitxer .fin.
     * @param array{skip_db?:bool} $opts
     */
    public function export(string $passphrase, array $opts = []): string
    {
        if (strlen($passphrase) < 8) {
            throw new \RuntimeException('La passphrase ha de tenir com a mínim 8 caràcters.');
        }
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Falta l\'extensió zip.');
        }

        $tmp = $this->tempDir();
        try {
            // 1) Bolcat de BD.
            if (empty($opts['skip_db'])) {
                (new BackupService($this->root))->dumpDatabase($tmp . '/database.sql');
            }
            // 2) Claus .pem.
            $keysSrc = $this->root . '/config/keys';
            if (is_dir($keysSrc)) {
                @mkdir($tmp . '/config/keys', 0700, true);
                foreach (glob($keysSrc . '/*') ?: [] as $f) {
                    if (is_file($f)) {
                        copy($f, $tmp . '/config/keys/' . basename($f));
                    }
                }
            }
            // 3) VERSION.
            if (is_file($this->root . '/VERSION')) {
                copy($this->root . '/VERSION', $tmp . '/VERSION');
            }

            // 4) Manifest amb checksums (de tot menys manifest.json).
            $files = $this->checksums($tmp);
            $manifest = [
                'app'        => 'finances',
                'version'    => is_file($this->root . '/VERSION') ? trim((string) file_get_contents($this->root . '/VERSION')) : '0.0.0',
                'created_at' => date('c'),
                'app_key'    => (string) Config::get('app.key', ''),
                'files'      => $files,
            ];
            file_put_contents($tmp . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // 5) ZIP + xifratge.
            $zipPath = $tmp . '/bundle.zip';
            $this->zipDir($tmp, $zipPath, ['bundle.zip']);
            $blob = Crypto::encryptWithPassphrase((string) file_get_contents($zipPath), $passphrase);

            $outDir = $this->root . '/storage/exports';
            if (!is_dir($outDir)) {
                @mkdir($outDir, 0750, true);
            }
            $out = $outDir . '/finances-migration-' . date('Ymd-His') . '.fin';
            file_put_contents($out, $blob);
            return $out;
        } finally {
            $this->rrmdir($tmp);
        }
    }

    /**
     * Importa un paquet de migració. Restaura BD, claus i APP_KEY.
     * @param array{skip_db?:bool,set_app_key?:bool} $opts
     * @return array{version:string,app_key:string}
     */
    public function import(string $file, string $passphrase, array $opts = []): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Fitxer de migració no trobat.');
        }
        $blob = (string) file_get_contents($file);
        $zipData = Crypto::decryptWithPassphrase($blob, $passphrase);

        $tmp = $this->tempDir();
        try {
            $zipPath = $tmp . '/bundle.zip';
            file_put_contents($zipPath, $zipData);
            $extract = $tmp . '/extract';
            @mkdir($extract, 0700, true);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \RuntimeException('No s\'ha pogut obrir el paquet.');
            }
            $zip->extractTo($extract);
            $zip->close();

            // Verifica manifest + checksums.
            $manifest = json_decode((string) @file_get_contents($extract . '/manifest.json'), true);
            if (!is_array($manifest) || ($manifest['app'] ?? '') !== 'finances') {
                throw new \RuntimeException('Manifest del paquet no vàlid.');
            }
            foreach (($manifest['files'] ?? []) as $rel => $hash) {
                $path = $extract . '/' . $rel;
                if (!is_file($path) || hash_file('sha256', $path) !== $hash) {
                    throw new \RuntimeException('Checksum incorrecte: ' . $rel);
                }
            }

            // Restaura BD.
            if (empty($opts['skip_db']) && is_file($extract . '/database.sql')) {
                DB::connection()->exec((string) file_get_contents($extract . '/database.sql'));
            }

            // Restaura claus .pem.
            $keysDst = $this->root . '/config/keys';
            if (is_dir($extract . '/config/keys')) {
                if (!is_dir($keysDst)) {
                    @mkdir($keysDst, 0700, true);
                }
                foreach (glob($extract . '/config/keys/*') ?: [] as $f) {
                    $dst = $keysDst . '/' . basename($f);
                    copy($f, $dst);
                    @chmod($dst, 0600);
                }
            }

            // Actualitza l'APP_KEY del config destí (perquè els secrets es desxifrin).
            $appKey = (string) ($manifest['app_key'] ?? '');
            if (($opts['set_app_key'] ?? true) && $appKey !== '') {
                $this->setAppKey($appKey);
            }

            return ['version' => (string) ($manifest['version'] ?? ''), 'app_key' => $appKey];
        } finally {
            $this->rrmdir($tmp);
        }
    }

    /** @return array<string,string> ruta relativa => sha256 */
    private function checksums(string $dir): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            if ($f->isFile()) {
                $rel = substr($f->getPathname(), strlen($dir) + 1);
                if ($rel === 'manifest.json') {
                    continue;
                }
                $out[$rel] = hash_file('sha256', $f->getPathname());
            }
        }
        return $out;
    }

    /** @param array<int,string> $exclude noms (basename) a excloure */
    private function zipDir(string $dir, string $zipPath, array $exclude = []): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No s\'ha pogut crear el ZIP.');
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            /** @var \SplFileInfo $f */
            if ($f->isFile() && !in_array($f->getFilename(), $exclude, true)) {
                $rel = substr($f->getPathname(), strlen($dir) + 1);
                $zip->addFile($f->getPathname(), $rel);
            }
        }
        $zip->close();
    }

    private function setAppKey(string $appKey): void
    {
        $cfgFile = $this->root . '/config/config.php';
        if (!is_file($cfgFile)) {
            return;
        }
        /** @var array<string,mixed> $cfg */
        $cfg = require $cfgFile;
        $cfg['app']['key'] = $appKey;
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($cfg, true) . ";\n";
        file_put_contents($cfgFile, $content, LOCK_EX);
        @chmod($cfgFile, 0640);
    }

    private function tempDir(): string
    {
        $d = sys_get_temp_dir() . '/finmig_' . bin2hex(random_bytes(6));
        @mkdir($d, 0700, true);
        return $d;
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
