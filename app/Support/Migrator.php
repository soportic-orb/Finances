<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Sistema de migracions versionades i idempotents.
 *
 * Les migracions són fitxers SQL a /database/migrations ordenats per nom
 * (0001_*.sql, 0002_*.sql…). S'apliquen les pendents i es registren a la taula
 * `schema_migrations` amb el seu checksum.
 */
final class Migrator
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (BASE_PATH . '/database/migrations');
    }

    private function ensureTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version    VARCHAR(255) NOT NULL PRIMARY KEY,
                checksum   CHAR(64)     NOT NULL,
                applied_at DATETIME     NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return string[] versions ja aplicades */
    private function applied(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $rows ?: []);
    }

    /** @return string[] rutes de fitxers de migració ordenades */
    private function files(): array
    {
        $files = glob($this->path . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    /**
     * Estat de les migracions.
     * @return array<int,array{version:string,applied:bool}>
     */
    public function status(): array
    {
        $pdo = DB::connection();
        $this->ensureTable($pdo);
        $applied = $this->applied($pdo);
        $out = [];
        foreach ($this->files() as $file) {
            $version = basename($file, '.sql');
            $out[] = ['version' => $version, 'applied' => in_array($version, $applied, true)];
        }
        return $out;
    }

    /**
     * Aplica totes les migracions pendents.
     * @return string[] versions aplicades en aquesta execució
     */
    public function migrate(): array
    {
        $pdo = DB::connection();
        $this->ensureTable($pdo);
        $applied = $this->applied($pdo);
        $done = [];

        foreach ($this->files() as $file) {
            $version = basename($file, '.sql');
            if (in_array($version, $applied, true)) {
                continue;
            }
            $sql = (string) file_get_contents($file);
            $checksum = hash('sha256', $sql);

            $pdo->beginTransaction();
            try {
                $pdo->exec($sql);
                $stmt = $pdo->prepare(
                    'INSERT INTO schema_migrations (version, checksum, applied_at) VALUES (?, ?, NOW())'
                );
                $stmt->execute([$version, $checksum]);
                $pdo->commit();
                $done[] = $version;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new \RuntimeException("Ha fallat la migració $version: " . $e->getMessage(), 0, $e);
            }
        }

        return $done;
    }
}
