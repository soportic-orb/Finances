<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Recurring
{
    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT * FROM recurring WHERE household_id = ? ORDER BY is_subscription DESC, label',
            [$householdId]
        )->fetchAll();
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM recurring WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }

    /** Esborra els detectats automàticament per regenerar-los. */
    public static function clearDetected(int $householdId): void
    {
        DB::run('DELETE FROM recurring WHERE household_id = ?', [$householdId]);
    }

    /** @param array<string,mixed> $d */
    public static function insert(int $householdId, array $d): int
    {
        DB::run(
            'INSERT INTO recurring (household_id, label, amount_est, cadence, next_expected_on, last_seen_on, occurrences, is_subscription, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $householdId, $d['label'], $d['amount_est'], $d['cadence'], $d['next_expected_on'],
                $d['last_seen_on'], $d['occurrences'], $d['is_subscription'], $d['status'],
            ]
        );
        return (int) DB::connection()->lastInsertId();
    }
}
