<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Goal
{
    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT g.*, a.name AS account_name, a.current_balance
             FROM goals g LEFT JOIN accounts a ON a.id = g.account_id
             WHERE g.household_id = ? ORDER BY g.target_date IS NULL, g.target_date, g.name',
            [$householdId]
        )->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM goals WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $d */
    public static function create(int $householdId, array $d): int
    {
        DB::run(
            'INSERT INTO goals (household_id, name, target_amount, current_amount, target_date, account_id) VALUES (?, ?, ?, ?, ?, ?)',
            [$householdId, $d['name'], $d['target_amount'], $d['current_amount'], $d['target_date'], $d['account_id']]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, int $householdId, array $d): void
    {
        DB::run(
            'UPDATE goals SET name = ?, target_amount = ?, current_amount = ?, target_date = ?, account_id = ? WHERE id = ? AND household_id = ?',
            [$d['name'], $d['target_amount'], $d['current_amount'], $d['target_date'], $d['account_id'], $id, $householdId]
        );
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM goals WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }
}
