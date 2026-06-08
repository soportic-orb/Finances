<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class EbSession
{
    public static function create(int $authorizationId, string $sessionId, string $status, ?string $validUntil): int
    {
        DB::run(
            'INSERT INTO eb_sessions (authorization_id, session_id, status, valid_until) VALUES (?, ?, ?, ?)',
            [$authorizationId, $sessionId, $status, $validUntil]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::run('SELECT * FROM eb_sessions WHERE id = ? LIMIT 1', [$id])->fetch();
        return $row ?: null;
    }

    public static function setStatus(int $id, string $status): void
    {
        DB::run('UPDATE eb_sessions SET status = ? WHERE id = ?', [$status, $id]);
    }
}
