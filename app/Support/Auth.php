<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Estat de sessió i autenticació.
 */
final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function householdId(): ?int
    {
        return isset($_SESSION['household_id']) ? (int) $_SESSION['household_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isOwner(): bool
    {
        return self::role() === 'owner';
    }

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /** Inicia la sessió autenticada (amb regeneració d'ID anti-fixació). */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']      = (int) $user['id'];
        $_SESSION['household_id'] = (int) $user['household_id'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['user'] = [
            'id'           => (int) $user['id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'role'         => $user['role'],
            'household_id' => (int) $user['household_id'],
        ];
        unset($_SESSION['pending_2fa_user_id']);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
