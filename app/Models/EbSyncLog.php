<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class EbSyncLog
{
    public static function start(int $linkId): int
    {
        DB::run('INSERT INTO eb_sync_log (eb_account_link_id, status) VALUES (?, "running")', [$linkId]);
        return (int) DB::connection()->lastInsertId();
    }

    public static function finish(int $id, int $new, int $dup, string $status, ?string $error = null): void
    {
        DB::run(
            'UPDATE eb_sync_log SET finished_at = NOW(), transactions_new = ?, transactions_dup = ?, status = ?, error = ? WHERE id = ?',
            [$new, $dup, $status, $error !== null ? mb_substr($error, 0, 500) : null, $id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function recentForHousehold(int $householdId, int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        return DB::run(
            "SELECT g.*, a.name AS account_name
             FROM eb_sync_log g
             JOIN eb_account_links l ON l.id = g.eb_account_link_id
             JOIN accounts a ON a.id = l.account_id
             WHERE a.household_id = ?
             ORDER BY g.id DESC LIMIT $limit",
            [$householdId]
        )->fetchAll();
    }
}
