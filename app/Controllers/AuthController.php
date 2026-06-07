<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Auth;
use App\Support\Totp;
use App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        View::render('auth/login', [
            'error' => flash('login_error'),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = $email !== '' ? User::findByEmail($email) : null;

        // Compte bloquejat per intents fallits.
        if ($user !== null && User::isLocked($user)) {
            flash('login_error', 'Compte bloquejat temporalment per massa intents. Torna-ho a provar més tard.');
            redirect('/login');
        }

        if ($user === null || !Auth::verifyPassword($password, $user['password_hash'])) {
            if ($user !== null) {
                User::registerFailedLogin((int) $user['id']);
                AuditLog::record('login_failed', 'user', (int) $user['id'], null, (int) $user['household_id'], (int) $user['id']);
            }
            flash('login_error', 'Credencials incorrectes.');
            redirect('/login');
        }

        User::clearFailedLogins((int) $user['id']);

        // 2FA: si té secret, exigeix codi abans de completar.
        if (!empty($user['totp_secret'])) {
            $_SESSION['pending_2fa_user_id'] = (int) $user['id'];
            redirect('/2fa');
        }

        Auth::login($user);
        AuditLog::record('login', 'user', (int) $user['id']);
        redirect('/dashboard');
    }

    public function show2fa(): void
    {
        if (empty($_SESSION['pending_2fa_user_id'])) {
            redirect('/login');
        }
        View::render('auth/twofactor', [
            'error' => flash('twofa_error'),
        ], 'layouts/auth');
    }

    public function verify2fa(): void
    {
        $pendingId = (int) ($_SESSION['pending_2fa_user_id'] ?? 0);
        if ($pendingId === 0) {
            redirect('/login');
        }
        $user = User::find($pendingId);
        $code = (string) ($_POST['code'] ?? '');

        if ($user === null || empty($user['totp_secret']) || !Totp::verify($user['totp_secret'], $code)) {
            AuditLog::record('login_2fa_failed', 'user', $pendingId, null, (int) ($user['household_id'] ?? 0), $pendingId);
            flash('twofa_error', 'Codi de verificació incorrecte.');
            redirect('/2fa');
        }

        Auth::login($user);
        AuditLog::record('login', 'user', (int) $user['id']);
        redirect('/dashboard');
    }

    public function logout(): void
    {
        AuditLog::record('logout', 'user', Auth::id());
        Auth::logout();
        redirect('/login');
    }
}
