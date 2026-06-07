<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class AiInsight
{
    public static function create(int $householdId, string $period, string $summary, string $recommendationsJson): int
    {
        DB::run(
            'INSERT INTO ai_insights (household_id, period, summary, recommendations_json) VALUES (?, ?, ?, ?)',
            [$householdId, $period, $summary, $recommendationsJson]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function latest(int $householdId): ?array
    {
        $row = DB::run(
            'SELECT * FROM ai_insights WHERE household_id = ? ORDER BY id DESC LIMIT 1',
            [$householdId]
        )->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(int $householdId, int $limit = 12): array
    {
        $limit = max(1, min(60, $limit));
        return DB::run(
            "SELECT * FROM ai_insights WHERE household_id = ? ORDER BY id DESC LIMIT $limit",
            [$householdId]
        )->fetchAll();
    }
}
