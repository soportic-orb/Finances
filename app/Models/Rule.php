<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Rule
{
    public const MATCH_TYPES = ['conte', 'regex', 'exacte'];
    public const FIELDS = ['description', 'merchant', 'counterparty', 'amount'];

    /** @return array<int,array<string,mixed>> ordenades per prioritat */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT r.*, c.name AS category_name
             FROM rules r LEFT JOIN categories c ON c.id = r.set_category_id
             WHERE r.household_id = ?
             ORDER BY r.priority ASC, r.id ASC',
            [$householdId]
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> només actives, per a l'aplicació */
    public static function enabledByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT * FROM rules WHERE household_id = ? AND enabled = 1 ORDER BY priority ASC, id ASC',
            [$householdId]
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM rules WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $d */
    public static function create(int $householdId, array $d): int
    {
        DB::run(
            'INSERT INTO rules (household_id, match_type, pattern, field, set_category_id, priority, enabled)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$householdId, $d['match_type'], $d['pattern'], $d['field'], $d['set_category_id'], $d['priority'], $d['enabled']]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, int $householdId, array $d): void
    {
        DB::run(
            'UPDATE rules SET match_type = ?, pattern = ?, field = ?, set_category_id = ?, priority = ?, enabled = ?
             WHERE id = ? AND household_id = ?',
            [$d['match_type'], $d['pattern'], $d['field'], $d['set_category_id'], $d['priority'], $d['enabled'], $id, $householdId]
        );
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM rules WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }
}
