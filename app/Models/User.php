<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

/**
 * Model d'usuaris (propietari/membres de la llar). Tot via PDO preparat.
 */
final class User
{
    public const MAX_FAILED = 5;
    public const LOCK_MINUTES = 15;

    /** @return array<string,mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $row = DB::run('SELECT * FROM users WHERE email = ? LIMIT 1', [$email])->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $row = DB::run('SELECT * FROM users WHERE id = ? LIMIT 1', [$id])->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public static function allByHousehold(int $householdId): array
    {
        return DB::run(
            'SELECT id, name, email, role, totp_secret, created_at
             FROM users WHERE household_id = ? ORDER BY role = "owner" DESC, name',
            [$householdId]
        )->fetchAll();
    }

    public static function create(int $householdId, string $name, string $email, string $passwordHash, string $role = 'member'): int
    {
        DB::run(
            'INSERT INTO users (household_id, name, email, password_hash, role)
             VALUES (?, ?, ?, ?, ?)',
            [$householdId, $name, $email, $passwordHash, $role]
        );
        return (int) DB::connection()->lastInsertId();
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM users WHERE id = ? AND household_id = ? AND role <> "owner"', [$id, $householdId]);
    }

    public static function isLocked(array $user): bool
    {
        return !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time();
    }

    public static function registerFailedLogin(int $id): void
    {
        $user = self::find($id);
        if ($user === null) {
            return;
        }
        $failed = (int) $user['failed_logins'] + 1;
        if ($failed >= self::MAX_FAILED) {
            DB::run(
                'UPDATE users SET failed_logins = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?',
                [self::LOCK_MINUTES, $id]
            );
        } else {
            DB::run('UPDATE users SET failed_logins = ? WHERE id = ?', [$failed, $id]);
        }
    }

    public static function clearFailedLogins(int $id): void
    {
        DB::run('UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?', [$id]);
    }

    public static function setTotpSecret(int $id, ?string $secret): void
    {
        DB::run('UPDATE users SET totp_secret = ? WHERE id = ?', [$secret, $id]);
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }
}
