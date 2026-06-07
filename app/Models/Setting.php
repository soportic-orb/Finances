<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

/**
 * Settings clau/valor per llar (i globals si household_id és NULL).
 */
final class Setting
{
    public static function get(int $householdId, string $key, ?string $default = null): ?string
    {
        $row = DB::run(
            'SELECT `value` FROM settings WHERE household_id = ? AND `key` = ? LIMIT 1',
            [$householdId, $key]
        )->fetch();
        return $row ? (string) $row['value'] : $default;
    }

    public static function set(int $householdId, string $key, string $value): void
    {
        DB::run(
            'INSERT INTO settings (household_id, `key`, `value`) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$householdId, $key, $value]
        );
    }

    /** @return array<string,string> */
    public static function all(int $householdId): array
    {
        $rows = DB::run('SELECT `key`, `value` FROM settings WHERE household_id = ?', [$householdId])->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['key']] = (string) $r['value'];
        }
        return $out;
    }
}
