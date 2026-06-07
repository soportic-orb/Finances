<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class EbAccountLink
{
    public static function create(int $accountId, int $sessionId, string $ebAccountUid, ?string $ibanHash): int
    {
        DB::run(
            'INSERT INTO eb_account_links (account_id, session_id, eb_account_uid, iban_hash) VALUES (?, ?, ?, ?)',
            [$accountId, $sessionId, $ebAccountUid, $ibanHash]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::run('SELECT * FROM eb_account_links WHERE id = ? LIMIT 1', [$id])->fetch();
        return $row ?: null;
    }

    public static function existsForUid(string $uid): bool
    {
        return (bool) DB::run('SELECT id FROM eb_account_links WHERE eb_account_uid = ? LIMIT 1', [$uid])->fetch();
    }

    public static function markSynced(int $id, ?string $continuationKey): void
    {
        DB::run(
            'UPDATE eb_account_links SET last_synced_at = NOW(), last_continuation_key = ? WHERE id = ?',
            [$continuationKey, $id]
        );
    }

    /**
     * Enllaços actius d'una llar amb info de compte i venciment de consentiment.
     * @return array<int,array<string,mixed>>
     */
    public static function activeByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT l.*, a.name AS account_name, a.household_id, s.valid_until, s.status AS session_status, s.session_id
             FROM eb_account_links l
             JOIN accounts a ON a.id = l.account_id
             JOIN eb_sessions s ON s.id = l.session_id
             WHERE a.household_id = ?
             ORDER BY a.name',
            [$householdId]
        )->fetchAll();
    }

    /** Tots els enllaços actius del sistema (per al cron). @return array<int,array<string,mixed>> */
    public static function allActive(): array
    {
        return DB::run(
            "SELECT l.*, a.name AS account_name, a.household_id, a.currency, s.valid_until, s.status AS session_status
             FROM eb_account_links l
             JOIN accounts a ON a.id = l.account_id
             JOIN eb_sessions s ON s.id = l.session_id
             WHERE a.archived = 0"
        )->fetchAll();
    }
}
