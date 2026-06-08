<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Guardes d'accés per a controllers.
 */
final class Guard
{
    /** Exigeix sessió autenticada; si no, redirigeix al login. */
    public static function requireAuth(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
    }

    /** Exigeix rol owner; 403 si és membre. */
    public static function requireOwner(): void
    {
        self::requireAuth();
        if (!Auth::isOwner()) {
            abort(403, 'Només el propietari de la llar pot fer aquesta acció.');
        }
    }
}
