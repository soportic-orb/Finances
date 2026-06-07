<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Crypto;
use App\Support\DB;

/**
 * Claus d'API xifrades en repòs (AES-256-GCM amb APP_KEY).
 */
final class ApiCredential
{
    /** Desa (xifrant) un secret per a un proveïdor. */
    public static function set(int $householdId, string $provider, string $plaintext): void
    {
        $enc = Crypto::encrypt($plaintext);
        DB::run(
            'INSERT INTO api_credentials (household_id, provider, secret_encrypted) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE secret_encrypted = VALUES(secret_encrypted)',
            [$householdId, $provider, $enc]
        );
    }

    /** Retorna el secret desxifrat o null. */
    public static function get(int $householdId, string $provider): ?string
    {
        $row = DB::run(
            'SELECT secret_encrypted FROM api_credentials WHERE household_id = ? AND provider = ? LIMIT 1',
            [$householdId, $provider]
        )->fetch();
        if (!$row) {
            return null;
        }
        try {
            return Crypto::decrypt((string) $row['secret_encrypted']);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function exists(int $householdId, string $provider): bool
    {
        return (bool) DB::run(
            'SELECT id FROM api_credentials WHERE household_id = ? AND provider = ? LIMIT 1',
            [$householdId, $provider]
        )->fetch();
    }

    public static function delete(int $householdId, string $provider): void
    {
        DB::run('DELETE FROM api_credentials WHERE household_id = ? AND provider = ?', [$householdId, $provider]);
    }
}
