<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class ImportTemplate
{
    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run('SELECT * FROM import_templates WHERE household_id = ? ORDER BY name', [$householdId])->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM import_templates WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    /** @param array<string,mixed> $config */
    public static function create(int $householdId, string $name, array $config): int
    {
        DB::run(
            'INSERT INTO import_templates (household_id, name, config_json) VALUES (?, ?, ?)',
            [$householdId, $name, json_encode($config, JSON_UNESCAPED_UNICODE)]
        );
        return (int) DB::connection()->lastInsertId();
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM import_templates WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }
}
