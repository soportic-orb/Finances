<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class EbAuthorization
{
    public static function create(int $householdId, ?int $userId, string $aspspName, string $country, string $state, string $psuType = 'personal'): int
    {
        DB::run(
            'INSERT INTO eb_authorizations (household_id, initiated_by_user_id, aspsp_name, aspsp_country, psu_type, state)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$householdId, $userId, $aspspName, $country, $psuType, $state]
        );
        return (int) DB::connection()->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function findByState(string $state, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM eb_authorizations WHERE state = ? AND household_id = ? LIMIT 1', [$state, $householdId])->fetch();
        return $row ?: null;
    }

    public static function update(int $id, string $authorizationId, string $status, ?string $validUntil): void
    {
        DB::run(
            'UPDATE eb_authorizations SET authorization_id = ?, status = ?, valid_until = ? WHERE id = ?',
            [$authorizationId, $status, $validUntil, $id]
        );
    }

    public static function setStatus(int $id, string $status): void
    {
        DB::run('UPDATE eb_authorizations SET status = ? WHERE id = ?', [$status, $id]);
    }
}
