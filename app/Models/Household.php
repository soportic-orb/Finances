<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Household
{
    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::run('SELECT * FROM households WHERE id = ? LIMIT 1', [$id])->fetch();
        return $row ?: null;
    }

    public static function update(int $id, string $name, string $currency, string $timezone, string $locale): void
    {
        DB::run(
            'UPDATE households SET name = ?, base_currency = ?, timezone = ?, locale = ? WHERE id = ?',
            [$name, $currency, $timezone, $locale, $id]
        );
    }

    public static function memberCount(int $id): int
    {
        return (int) DB::run('SELECT COUNT(*) AS c FROM users WHERE household_id = ?', [$id])->fetch()['c'];
    }
}
