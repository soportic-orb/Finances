<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Models\User;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\Totp;
use App\Support\View;

final class SettingsController
{
    public function index(): void
    {
        Guard::requireAuth();
        $user = User::find((int) Auth::id());

        View::render('settings/index', [
            'household'   => Household::find((int) Auth::householdId()),
            'user'        => $user,
            'has2fa'      => !empty($user['totp_secret']),
            'isOwner'     => Auth::isOwner(),
            'ok'          => flash('settings_ok'),
            'error'       => flash('settings_error'),
        ], 'layouts/app');
    }

    /** Actualització de dades de la llar (només owner). */
    public function updateHousehold(): void
    {
        Guard::requireOwner();
        $id = (int) Auth::householdId();

        $name = trim($_POST['name'] ?? '');
        $currency = trim($_POST['currency'] ?? 'EUR');
        $timezone = trim($_POST['timezone'] ?? 'Europe/Madrid');
        $locale = trim($_POST['locale'] ?? 'ca');

        if ($name === '' || !in_array($locale, ['ca', 'es'], true) || @timezone_open($timezone) === false) {
            flash('settings_error', 'Dades de la llar no vàlides.');
            redirect('/settings');
        }

        Household::update($id, $name, $currency, $timezone, $locale);
        AuditLog::record('settings_updated', 'household', $id);
        flash('settings_ok', 'Configuració de la llar desada.');
        redirect('/settings');
    }

    /** Mostra l'assistent d'activació de 2FA (genera secret pendent a la sessió). */
    public function setup2fa(): void
    {
        Guard::requireAuth();
        $user = User::find((int) Auth::id());
        if (!empty($user['totp_secret'])) {
            redirect('/settings');
        }

        $secret = $_SESSION['pending_totp_secret'] ?? Totp::generateSecret();
        $_SESSION['pending_totp_secret'] = $secret;

        $issuer = (string) (config('app.url') ?: 'Finances');
        $uri = Totp::provisioningUri($secret, (string) $user['email'], 'Finances');

        View::render('settings/twofactor', [
            'secret' => $secret,
            'uri'    => $uri,
            'error'  => flash('twofa_setup_error'),
        ], 'layouts/app');
    }

    /** Confirma i activa el 2FA verificant un primer codi. */
    public function enable2fa(): void
    {
        Guard::requireAuth();
        $secret = $_SESSION['pending_totp_secret'] ?? '';
        $code = (string) ($_POST['code'] ?? '');

        if ($secret === '' || !Totp::verify($secret, $code)) {
            flash('twofa_setup_error', 'Codi incorrecte. Torna-ho a provar.');
            redirect('/settings/2fa');
        }

        User::setTotpSecret((int) Auth::id(), $secret);
        unset($_SESSION['pending_totp_secret']);
        AuditLog::record('2fa_enabled', 'user', Auth::id());
        flash('settings_ok', 'Verificació en dos passos activada.');
        redirect('/settings');
    }

    /** Desactiva el 2FA (requereix contrasenya actual). */
    public function disable2fa(): void
    {
        Guard::requireAuth();
        $password = (string) ($_POST['password'] ?? '');
        $user = User::find((int) Auth::id());

        if ($user === null || !Auth::verifyPassword($password, $user['password_hash'])) {
            flash('settings_error', 'Contrasenya incorrecta; el 2FA no s\'ha desactivat.');
            redirect('/settings');
        }

        User::setTotpSecret((int) Auth::id(), null);
        AuditLog::record('2fa_disabled', 'user', Auth::id());
        flash('settings_ok', 'Verificació en dos passos desactivada.');
        redirect('/settings');
    }
}
