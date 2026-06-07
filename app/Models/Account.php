<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Account
{
    public const TYPES = ['corrent', 'estalvi', 'efectiu', 'targeta', 'inversio'];

    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId, bool $includeArchived = false): array
    {
        $sql = 'SELECT a.*, u.name AS owner_name
                FROM accounts a
                LEFT JOIN users u ON u.id = a.owner_user_id
                WHERE a.household_id = ?';
        if (!$includeArchived) {
            $sql .= ' AND a.archived = 0';
        }
        $sql .= ' ORDER BY a.archived, a.name';
        return DB::run($sql, [$householdId])->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM accounts WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $d */
    public static function create(int $householdId, array $d): int
    {
        DB::run(
            'INSERT INTO accounts (household_id, owner_user_id, name, type, currency, opening_balance, current_balance, iban_last4, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $householdId,
                $d['owner_user_id'] ?: null,
                $d['name'],
                $d['type'],
                $d['currency'],
                $d['opening_balance'],
                $d['opening_balance'], // saldo inicial = saldo actual fins que hi hagi moviments
                $d['iban_last4'] ?: null,
                $d['source'] ?? 'manual',
            ]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, int $householdId, array $d): void
    {
        DB::run(
            'UPDATE accounts SET owner_user_id = ?, name = ?, type = ?, currency = ?, opening_balance = ?, iban_last4 = ?
             WHERE id = ? AND household_id = ?',
            [
                $d['owner_user_id'] ?: null,
                $d['name'],
                $d['type'],
                $d['currency'],
                $d['opening_balance'],
                $d['iban_last4'] ?: null,
                $id,
                $householdId,
            ]
        );
        self::recalc($id, $householdId);
    }

    public static function setArchived(int $id, int $householdId, bool $archived): void
    {
        DB::run('UPDATE accounts SET archived = ? WHERE id = ? AND household_id = ?', [$archived ? 1 : 0, $id, $householdId]);
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM accounts WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }

    /** Recalcula el saldo actual a partir del saldo inicial i els moviments. */
    public static function recalc(int $id, int $householdId): void
    {
        DB::run(
            'UPDATE accounts
             SET current_balance = opening_balance + COALESCE(
                 (SELECT SUM(amount) FROM transactions WHERE account_id = accounts.id), 0)
             WHERE id = ? AND household_id = ?',
            [$id, $householdId]
        );
    }

    public static function netWorth(int $householdId): float
    {
        $row = DB::run(
            'SELECT COALESCE(SUM(current_balance), 0) AS net FROM accounts WHERE household_id = ? AND archived = 0',
            [$householdId]
        )->fetch();
        return (float) $row['net'];
    }

    /** @return array<int,string> id => name (no arxivats) */
    public static function forSelect(int $householdId): array
    {
        $rows = DB::run(
            'SELECT id, name FROM accounts WHERE household_id = ? AND archived = 0 ORDER BY name',
            [$householdId]
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = (string) $r['name'];
        }
        return $out;
    }
}
