<?php

declare(strict_types=1);

use App\Services\MigrationService;
use App\Support\Config;
use App\Support\Crypto;
use App\Support\DB;
use App\Support\Migrator;

/**
 * Lògica de l'instal·lador web, aïllada del core de l'aplicació.
 *
 * No depèn de cap CDN ni de config.php (que encara no existeix). Reutilitza
 * només la capa Support pròpia (DB, Crypto, Migrator).
 */
final class Installer
{
    public const LOCK_FILE = '/install/.lock';

    /** L'aplicació ja està instal·lada / bloquejada? */
    public function isLocked(): bool
    {
        return is_file(BASE_PATH . self::LOCK_FILE) || is_file(BASE_PATH . '/config/config.php');
    }

    /**
     * Comprovació de requisits.
     * @return array<int,array{label:string,ok:bool,detail:string}>
     */
    public function requirements(): array
    {
        $checks = [];

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = ['label' => 'PHP ≥ 8.2', 'ok' => $phpOk, 'detail' => PHP_VERSION];

        foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl', 'zip', 'json'] as $ext) {
            $ok = extension_loaded($ext);
            $checks[] = ['label' => "Extensió $ext", 'ok' => $ok, 'detail' => $ok ? 'present' : 'absent'];
        }

        foreach (['/config', '/config/keys', '/storage'] as $dir) {
            $path = BASE_PATH . $dir;
            if ($dir === '/config/keys' && !is_dir($path) && is_writable(BASE_PATH . '/config')) {
                @mkdir($path, 0700, true);
            }
            $ok = is_dir($path) && is_writable($path);
            $checks[] = ['label' => "Escriptura $dir", 'ok' => $ok, 'detail' => $path];
        }

        return $checks;
    }

    public function requirementsPass(): bool
    {
        foreach ($this->requirements() as $c) {
            if (!$c['ok']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Prova de connexió a la base de dades.
     * @param array<string,mixed> $db
     */
    public function testDatabase(array $db): bool
    {
        return DB::test($db);
    }

    /**
     * Executa la instal·lació completa de manera transaccional.
     *
     * @param array{db:array<string,mixed>,app:array<string,string>,owner:array<string,string>} $data
     * @return array{household_id:int,owner_id:int}
     */
    public function install(array $data): array
    {
        // 1) Genera config.php amb una APP_KEY nova.
        $this->writeConfig($data['db'], $data['app']);

        // 2) Recarrega la configuració i reinicia la connexió per usar config.php.
        Config::load();
        DB::reset();

        // 3) Migracions.
        (new Migrator())->migrate();

        // 4) Llar + propietari + categories per defecte (transaccional).
        $pdo = DB::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO households (name, base_currency, timezone, locale) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $data['app']['household_name'],
                $data['app']['currency'],
                $data['app']['timezone'],
                $data['app']['locale'],
            ]);
            $householdId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO users (household_id, email, password_hash, name, role)
                 VALUES (?, ?, ?, ?, "owner")'
            );
            $stmt->execute([
                $householdId,
                $data['owner']['email'],
                password_hash($data['owner']['password'], PASSWORD_ARGON2ID),
                $data['owner']['name'],
            ]);
            $ownerId = (int) $pdo->lastInsertId();

            $this->seedCategories($householdId);
            $this->seedSettings($householdId, $data['app']);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // 5) Bloqueja l'instal·lador.
        $this->lock();

        return ['household_id' => $householdId, 'owner_id' => $ownerId];
    }

    /**
     * @param array<string,mixed>  $db
     * @param array<string,string> $app
     */
    private function writeConfig(array $db, array $app): void
    {
        $config = [
            'app' => [
                'key'      => Crypto::generateAppKey(),
                'env'      => 'production',
                'debug'    => false,
                'url'      => rtrim($app['url'] ?? '', '/'),
                'locale'   => $app['locale'],
                'timezone' => $app['timezone'],
            ],
            'db' => [
                'host'    => $db['host'],
                'port'    => (int) $db['port'],
                'name'    => $db['name'],
                'user'    => $db['user'],
                'pass'    => $db['pass'],
                'charset' => 'utf8mb4',
            ],
        ];

        $content = "<?php\n\ndeclare(strict_types=1);\n\n// Generat per l'instal·lador. NO el pugis a Git.\nreturn "
            . var_export($config, true) . ";\n";

        $file = BASE_PATH . '/config/config.php';
        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new \RuntimeException('No s\'ha pogut escriure config/config.php.');
        }
        @chmod($file, 0640);
    }

    private function seedCategories(int $householdId): void
    {
        $seedFile = BASE_PATH . '/database/seeds/categories_ca.php';
        if (!is_file($seedFile)) {
            return;
        }
        /** @var array<int,array<string,mixed>> $groups */
        $groups = require $seedFile;
        $pdo = DB::connection();

        $insParent = $pdo->prepare(
            'INSERT INTO categories (household_id, parent_id, name, kind, icon, color)
             VALUES (?, NULL, ?, ?, ?, ?)'
        );
        $insChild = $pdo->prepare(
            'INSERT INTO categories (household_id, parent_id, name, kind, icon, color)
             VALUES (?, ?, ?, ?, ?, NULL)'
        );

        foreach ($groups as $group) {
            $insParent->execute([
                $householdId,
                $group['name'],
                $group['kind'],
                $group['icon'] ?? null,
                $group['color'] ?? null,
            ]);
            $parentId = (int) $pdo->lastInsertId();
            foreach (($group['children'] ?? []) as $child) {
                $insChild->execute([
                    $householdId,
                    $parentId,
                    $child['name'],
                    $group['kind'],
                    $child['icon'] ?? null,
                ]);
            }
        }
    }

    /** @param array<string,string> $app */
    private function seedSettings(int $householdId, array $app): void
    {
        $pdo = DB::connection();
        $stmt = $pdo->prepare('INSERT INTO settings (household_id, `key`, `value`) VALUES (?, ?, ?)');
        foreach (['locale' => $app['locale'], 'currency' => $app['currency']] as $key => $value) {
            $stmt->execute([$householdId, $key, $value]);
        }
    }

    /**
     * Instal·lació per restauració d'un paquet de migració d'un altre servidor.
     * @param array<string,mixed> $db  credencials de la BD del servidor nou
     */
    public function importMigration(array $db, string $bundleFile, string $passphrase): void
    {
        // 1) Config amb les credencials NOVES i una APP_KEY temporal.
        $this->writeConfig($db, [
            'url'      => '',
            'locale'   => 'ca',
            'timezone' => 'Europe/Madrid',
        ]);
        Config::load();
        DB::reset();

        // 2) Importa: restaura BD + claus i fixa l'APP_KEY del paquet al config.
        (new MigrationService())->import($bundleFile, $passphrase);

        // 3) Recarrega config amb l'APP_KEY importada i aplica migracions pendents.
        Config::load();
        DB::reset();
        (new Migrator())->migrate();

        // 4) Bloqueja l'instal·lador.
        $this->lock();
    }

    public function lock(): void
    {
        @file_put_contents(BASE_PATH . self::LOCK_FILE, 'installed at ' . date('c') . "\n");
    }
}
