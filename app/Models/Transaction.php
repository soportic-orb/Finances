<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Transaction
{
    /**
     * Construeix la clàusula WHERE i els paràmetres a partir de filtres validats.
     * @param array<string,mixed> $f
     * @return array{0:string,1:array<int,mixed>}
     */
    private static function buildWhere(int $householdId, array $f): array
    {
        $where = ['t.household_id = ?'];
        $params = [$householdId];

        if (!empty($f['account'])) {
            $where[] = 't.account_id = ?';
            $params[] = (int) $f['account'];
        }
        if (!empty($f['category'])) {
            $where[] = 't.category_id = ?';
            $params[] = (int) $f['category'];
        }
        if (!empty($f['type']) && in_array($f['type'], ['income', 'expense', 'transfer'], true)) {
            $where[] = 't.type = ?';
            $params[] = $f['type'];
        }
        if (!empty($f['member'])) {
            $where[] = 'a.owner_user_id = ?';
            $params[] = (int) $f['member'];
        }
        if (!empty($f['from'])) {
            $where[] = 't.occurred_on >= ?';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 't.occurred_on <= ?';
            $params[] = $f['to'];
        }
        if (isset($f['q']) && $f['q'] !== '') {
            $where[] = '(t.description LIKE ? OR t.merchant LIKE ? OR t.counterparty LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        if (isset($f['min']) && $f['min'] !== '') {
            $where[] = 'ABS(t.amount) >= CAST(? AS DECIMAL(14,2))';
            $params[] = (float) $f['min'];
        }
        if (isset($f['max']) && $f['max'] !== '') {
            $where[] = 'ABS(t.amount) <= CAST(? AS DECIMAL(14,2))';
            $params[] = (float) $f['max'];
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function query(int $householdId, array $filters, int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = self::buildWhere($householdId, $filters);
        $limit = max(1, min(100000, $limit));
        $offset = max(0, $offset);

        $sql = "SELECT t.*, a.name AS account_name, a.currency AS account_currency,
                       c.name AS category_name, u.name AS owner_name
                FROM transactions t
                JOIN accounts a ON a.id = t.account_id
                LEFT JOIN categories c ON c.id = t.category_id
                LEFT JOIN users u ON u.id = a.owner_user_id
                WHERE $where
                ORDER BY t.occurred_on DESC, t.id DESC
                LIMIT $limit OFFSET $offset";
        return DB::run($sql, $params)->fetchAll();
    }

    /**
     * Totes les files que compleixen els filtres (per a exportació, sense paginar).
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public static function allForExport(int $householdId, array $filters, int $cap = 100000): array
    {
        return self::query($householdId, $filters, $cap, 0);
    }

    /** @param array<string,mixed> $filters */
    public static function count(int $householdId, array $filters): int
    {
        [$where, $params] = self::buildWhere($householdId, $filters);
        $sql = "SELECT COUNT(*) AS c FROM transactions t JOIN accounts a ON a.id = t.account_id WHERE $where";
        return (int) DB::run($sql, $params)->fetch()['c'];
    }

    /** Suma amb signe dels moviments filtrats (per a totals). */
    public static function sum(int $householdId, array $filters): float
    {
        [$where, $params] = self::buildWhere($householdId, $filters);
        $sql = "SELECT COALESCE(SUM(t.amount),0) AS s FROM transactions t JOIN accounts a ON a.id = t.account_id WHERE $where";
        return (float) DB::run($sql, $params)->fetch()['s'];
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM transactions WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    public static function setCategory(int $id, int $householdId, ?int $categoryId): void
    {
        DB::run('UPDATE transactions SET category_id = ? WHERE id = ? AND household_id = ?', [$categoryId, $id, $householdId]);
    }

    /**
     * Files per a categorització per regles (exclou traspassos).
     * @return array<int,array<string,mixed>>
     */
    public static function rowsForCategorization(int $householdId, bool $onlyUncategorized): array
    {
        $sql = "SELECT id, category_id, description, merchant, counterparty, amount
                FROM transactions WHERE household_id = ? AND type <> 'transfer'";
        if ($onlyUncategorized) {
            $sql .= ' AND category_id IS NULL';
        }
        return DB::run($sql, [$householdId])->fetchAll();
    }

    /** Deduplicació d'ingesta: existeix ja aquesta referència externa al compte? */
    public static function existsExternal(int $accountId, string $externalRef): bool
    {
        $row = DB::run(
            'SELECT id FROM transactions WHERE account_id = ? AND external_ref = ? LIMIT 1',
            [$accountId, $externalRef]
        )->fetch();
        return (bool) $row;
    }

    /**
     * Deduplicació d'importació (cross-source): mateix compte, data i import.
     * Permet detectar moviments ja entrats per Enable Banking encara que la
     * descripció difereixi.
     */
    public static function existsSimilar(int $accountId, string $date, float $amount): bool
    {
        $row = DB::run(
            'SELECT id FROM transactions
             WHERE account_id = ? AND occurred_on = ? AND ABS(amount - CAST(? AS DECIMAL(14,2))) < 0.005
             LIMIT 1',
            [$accountId, $date, $amount]
        )->fetch();
        return (bool) $row;
    }

    private static function dedupHash(int $accountId, string $date, float $amount, ?string $desc): string
    {
        $norm = mb_strtolower(trim((string) $desc));
        $norm = preg_replace('/\s+/', ' ', $norm) ?? '';
        return hash('sha256', $accountId . '|' . $date . '|' . number_format($amount, 2, '.', '') . '|' . $norm);
    }

    /**
     * @param array<string,mixed> $d
     * @return int id de la transacció creada
     */
    public static function create(int $householdId, array $d): int
    {
        $hash = self::dedupHash((int) $d['account_id'], $d['occurred_on'], (float) $d['amount'], $d['description'] ?? null);
        DB::run(
            'INSERT INTO transactions
             (household_id, account_id, category_id, type, amount, currency, occurred_on, value_date,
              description, merchant, counterparty, notes, transfer_group_id, source, external_ref, dedup_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $householdId,
                $d['account_id'],
                $d['category_id'] ?: null,
                $d['type'],
                $d['amount'],
                $d['currency'],
                $d['occurred_on'],
                $d['value_date'] ?: null,
                $d['description'] ?: null,
                $d['merchant'] ?: null,
                $d['counterparty'] ?: null,
                $d['notes'] ?: null,
                $d['transfer_group_id'] ?? null,
                $d['source'] ?? 'manual',
                $d['external_ref'] ?? null,
                $hash,
            ]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public static function update(int $id, int $householdId, array $d): void
    {
        DB::run(
            'UPDATE transactions SET category_id = ?, type = ?, amount = ?, occurred_on = ?, value_date = ?,
                 description = ?, merchant = ?, counterparty = ?, notes = ?
             WHERE id = ? AND household_id = ?',
            [
                $d['category_id'] ?: null,
                $d['type'],
                $d['amount'],
                $d['occurred_on'],
                $d['value_date'] ?: null,
                $d['description'] ?: null,
                $d['merchant'] ?: null,
                $d['counterparty'] ?: null,
                $d['notes'] ?: null,
                $id,
                $householdId,
            ]
        );
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM transactions WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }

    /**
     * Crea un traspàs entre dos comptes (dues files amb el mateix grup).
     * @return string transfer_group_id
     */
    public static function createTransfer(
        int $householdId,
        int $fromAccount,
        int $toAccount,
        float $amount,
        string $currency,
        string $date,
        ?string $description,
        ?string $notes,
        ?int $categoryId = null
    ): string {
        $group = uuid4();
        $amount = abs($amount);

        self::create($householdId, [
            'account_id' => $fromAccount, 'category_id' => $categoryId, 'type' => 'transfer',
            'amount' => -$amount, 'currency' => $currency, 'occurred_on' => $date,
            'value_date' => null, 'description' => $description, 'merchant' => null,
            'counterparty' => null, 'notes' => $notes, 'transfer_group_id' => $group,
        ]);
        self::create($householdId, [
            'account_id' => $toAccount, 'category_id' => $categoryId, 'type' => 'transfer',
            'amount' => $amount, 'currency' => $currency, 'occurred_on' => $date,
            'value_date' => null, 'description' => $description, 'merchant' => null,
            'counterparty' => null, 'notes' => $notes, 'transfer_group_id' => $group,
        ]);

        return $group;
    }

    /** @return array<int,int> ids de comptes afectats per un grup de traspàs */
    public static function accountsInGroup(string $group, int $householdId): array
    {
        $rows = DB::run(
            'SELECT DISTINCT account_id FROM transactions WHERE transfer_group_id = ? AND household_id = ?',
            [$group, $householdId]
        )->fetchAll();
        return array_map(static fn ($r) => (int) $r['account_id'], $rows);
    }

    public static function deleteGroup(string $group, int $householdId): void
    {
        DB::run('DELETE FROM transactions WHERE transfer_group_id = ? AND household_id = ?', [$group, $householdId]);
    }
}
