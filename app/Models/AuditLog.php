<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auth;
use App\Support\DB;

/**
 * Registre d'auditoria. No s'hi desen mai dades sensibles.
 */
final class AuditLog
{
    public static function record(
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?string $meta = null,
        ?int $householdId = null,
        ?int $userId = null
    ): void {
        $householdId ??= Auth::householdId();
        $userId ??= Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        DB::run(
            'INSERT INTO audit_log (household_id, user_id, action, entity, entity_id, ip, meta)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$householdId, $userId, $action, $entity, $entityId, $ip, $meta]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public static function recent(int $householdId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return DB::run(
            "SELECT a.action, a.entity, a.entity_id, a.ip, a.meta, a.created_at, u.name AS user_name
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.household_id = ?
             ORDER BY a.id DESC
             LIMIT $limit",
            [$householdId]
        )->fetchAll();
    }
}
