<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Budget
{
    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT b.*, c.name AS category_name
             FROM budgets b JOIN categories c ON c.id = b.category_id
             WHERE b.household_id = ? ORDER BY c.name',
            [$householdId]
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM budgets WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $d */
    public static function create(int $householdId, array $d): int
    {
        DB::run(
            'INSERT INTO budgets (household_id, category_id, period, amount, start_on, rollover) VALUES (?, ?, ?, ?, ?, ?)',
            [$householdId, $d['category_id'], $d['period'], $d['amount'], $d['start_on'], $d['rollover']]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, int $householdId, array $d): void
    {
        DB::run(
            'UPDATE budgets SET category_id = ?, period = ?, amount = ?, start_on = ?, rollover = ? WHERE id = ? AND household_id = ?',
            [$d['category_id'], $d['period'], $d['amount'], $d['start_on'], $d['rollover'], $id, $householdId]
        );
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM budgets WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }
}
