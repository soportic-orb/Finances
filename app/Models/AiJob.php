<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class AiJob
{
    public static function log(int $householdId, string $type, string $model, int $tokensIn, int $tokensOut, string $status, ?string $payloadSummary): int
    {
        DB::run(
            'INSERT INTO ai_jobs (household_id, type, model, tokens_in, tokens_out, status, payload_summary)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$householdId, $type, $model, $tokensIn, $tokensOut, $status, $payloadSummary !== null ? mb_substr($payloadSummary, 0, 500) : null]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** Tokens (in+out) consumits aquest mes natural. */
    public static function tokensThisMonth(int $householdId): int
    {
        $row = DB::run(
            "SELECT COALESCE(SUM(tokens_in + tokens_out),0) AS t
             FROM ai_jobs
             WHERE household_id = ? AND created_at >= ?",
            [$householdId, date('Y-m-01 00:00:00')]
        )->fetch();
        return (int) $row['t'];
    }

    /** @return array<int,array<string,mixed>> */
    public static function recent(int $householdId, int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        return DB::run(
            "SELECT type, model, tokens_in, tokens_out, status, payload_summary, created_at
             FROM ai_jobs WHERE household_id = ? ORDER BY id DESC LIMIT $limit",
            [$householdId]
        )->fetchAll();
    }
}
