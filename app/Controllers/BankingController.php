<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\EbAccountLink;
use App\Models\EbAuthorization;
use App\Models\EbSession;
use App\Models\Setting;
use App\Services\EbSyncService;
use App\Services\EnableBankingService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

/**
 * Vinculació i sincronització bancària amb Enable Banking. Accions sensibles:
 * només el propietari de la llar.
 */
final class BankingController
{
    private function service(): EnableBankingService
    {
        return new EnableBankingService((int) Auth::householdId());
    }

    /** Registre de diagnòstic d'Enable Banking (sense secrets). */
    private function log(string $msg): void
    {
        @file_put_contents(
            BASE_PATH . '/storage/logs/eb.log',
            '[' . date('c') . '] ' . $msg . "\n",
            FILE_APPEND
        );
    }

    public function index(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $eb = $this->service();

        $aspsps = [];
        $aspspError = null;
        if ($eb->isConfigured()) {
            try {
                $resp = $eb->getAspsps('ES');
                if ($resp['status'] === 200 && is_array($resp['json'])) {
                    $aspsps = $resp['json']['aspsps'] ?? [];
                } else {
                    $aspspError = 'Error en obtenir la llista de bancs (' . $resp['status'] . ').';
                }
            } catch (\Throwable $e) {
                $aspspError = $e->getMessage();
            }
        }

        View::render('banking/index', [
            'configured'  => $eb->isConfigured(),
            'environment' => $eb->environment(),
            'links'       => EbAccountLink::activeByHousehold($hid),
            'syncLog'     => \App\Models\EbSyncLog::recentForHousehold($hid),
            'aspsps'      => $aspsps,
            'aspspError'  => $aspspError,
            'ok'          => flash('eb_ok'),
            'error'       => flash('eb_error'),
        ], 'layouts/app');
    }

    public function settings(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $eb = $this->service();

        View::render('banking/settings', [
            'application_id' => $eb->applicationId(),
            'environment'    => $eb->environment(),
            'base_url'       => $eb->baseUrl(),
            'redirect_url'   => $eb->redirectUrl(),
            'psu_ip'         => Setting::get($hid, 'eb_psu_ip', ''),
            'psu_ua'         => Setting::get($hid, 'eb_psu_ua', 'Finances/1.0'),
            'has_key'        => is_file($eb->keyPath()),
            'ok'             => flash('eb_ok'),
            'error'          => flash('eb_error'),
        ], 'layouts/app');
    }

    public function saveSettings(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();

        $appId = trim($_POST['application_id'] ?? '');
        $env = in_array($_POST['environment'] ?? '', ['sandbox', 'production'], true) ? $_POST['environment'] : 'production';
        $baseUrl = trim($_POST['base_url'] ?? '') ?: EnableBankingService::DEFAULT_BASE_URL;
        $redirect = trim($_POST['redirect_url'] ?? '');

        if ($appId === '' || !preg_match('/^[A-Za-z0-9\-]+$/', $appId)) {
            flash('eb_error', __('eb.invalid_appid'));
            redirect('/banking/settings');
        }

        Setting::set($hid, 'eb_application_id', $appId);
        Setting::set($hid, 'eb_environment', $env);
        Setting::set($hid, 'eb_base_url', $baseUrl);
        Setting::set($hid, 'eb_redirect_url', $redirect);
        Setting::set($hid, 'eb_psu_ip', trim($_POST['psu_ip'] ?? ''));
        Setting::set($hid, 'eb_psu_ua', trim($_POST['psu_ua'] ?? 'Finances/1.0'));

        // Pujada opcional de la clau privada .pem.
        if (!empty($_FILES['pem']['tmp_name']) && is_uploaded_file($_FILES['pem']['tmp_name'])) {
            $pem = (string) file_get_contents($_FILES['pem']['tmp_name']);
            if (openssl_pkey_get_private($pem) === false) {
                flash('eb_error', __('eb.invalid_pem'));
                redirect('/banking/settings');
            }
            $dir = BASE_PATH . '/config/keys';
            if (!is_dir($dir)) {
                @mkdir($dir, 0700, true);
            }
            $path = $dir . '/' . $appId . '.pem';
            file_put_contents($path, $pem);
            @chmod($path, 0600);
            $this->service()->clearJwtCache();
        }

        AuditLog::record('eb_settings_updated', 'eb', null);
        flash('eb_ok', __('eb.saved'));
        redirect('/banking/settings');
    }

    /** Inicia la vinculació d'un banc. */
    public function startLink(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $eb = $this->service();

        $aspspName = trim($_POST['aspsp_name'] ?? '');
        $this->log("startLink: aspsp='$aspspName' configured=" . ($eb->isConfigured() ? '1' : '0')
            . " redirect='" . $eb->redirectUrl() . "' env=" . $eb->environment());

        if ($aspspName === '' || !$eb->isConfigured()) {
            flash('eb_error', __('eb.invalid_bank'));
            redirect('/banking');
        }
        // La URL de callback és obligatòria per a /auth.
        if (trim($eb->redirectUrl()) === '') {
            flash('eb_error', __('eb.missing_redirect'));
            redirect('/banking/settings');
        }

        $state = uuid4();
        $validUntil = (new \DateTime('+89 days'))->format('Y-m-d\TH:i:s.v\Z');

        try {
            $resp = $eb->startAuthorization($aspspName, 'ES', $validUntil, $state);
            $this->log("startAuthorization: status=" . $resp['status']
                . " hasUrl=" . (!empty($resp['json']['url']) ? '1' : '0')
                . " body=" . substr((string) ($resp['body'] ?? ''), 0, 400));

            if ($resp['status'] >= 200 && $resp['status'] < 300 && !empty($resp['json']['url'])) {
                $authId = EbAuthorization::create($hid, Auth::id(), $aspspName, 'ES', $state);
                EbAuthorization::update($authId, (string) ($resp['json']['authorization_id'] ?? ''), 'redirected', null);
                AuditLog::record('eb_auth_started', 'eb_authorization', $authId, $aspspName);
                $this->log("redirect cap a consentiment OK");
                header('Location: ' . $resp['json']['url']);
                exit;
            }
            $detail = $this->ebError($resp);
            flash('eb_error', __('eb.auth_failed') . ' (' . $resp['status'] . ') ' . $detail);
        } catch (\Throwable $e) {
            $this->log("EXCEPCIÓ startAuthorization: " . $e->getMessage());
            flash('eb_error', $e->getMessage());
        }
        redirect('/banking');
    }

    /** Callback de consentiment: ?code=...&state=... */
    public function callback(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $eb = $this->service();

        $code = trim($_GET['code'] ?? '');
        $state = trim($_GET['state'] ?? '');

        $auth = $state !== '' ? EbAuthorization::findByState($state, $hid) : null;
        if ($auth === null || $code === '') {
            flash('eb_error', __('eb.bad_state'));
            redirect('/banking');
        }

        try {
            $resp = $eb->createSession($code);
            if ($resp['status'] < 200 || $resp['status'] >= 300 || empty($resp['json']['session_id'])) {
                EbAuthorization::setStatus((int) $auth['id'], 'failed');
                flash('eb_error', __('eb.session_failed') . ' (' . $resp['status'] . ')');
                redirect('/banking');
            }

            $json = $resp['json'];
            $validUntil = $this->parseValidUntil($json);
            EbAuthorization::update((int) $auth['id'], (string) ($auth['authorization_id'] ?? ''), 'authorized', $validUntil);
            $sessionRowId = EbSession::create((int) $auth['id'], (string) $json['session_id'], (string) ($json['status'] ?? 'AUTHORIZED'), $validUntil);

            $created = $this->persistAccounts($json['accounts'] ?? [], $sessionRowId, $hid);
            AuditLog::record('eb_session_created', 'eb_session', $sessionRowId, $created . ' comptes');
            flash('eb_ok', __('eb.linked', ['n' => $created]));
        } catch (\Throwable $e) {
            flash('eb_error', $e->getMessage());
        }
        redirect('/banking');
    }

    /** Extreu un missatge llegible d'una resposta d'error d'Enable Banking. @param array<string,mixed> $resp */
    private function ebError(array $resp): string
    {
        $j = $resp['json'] ?? null;
        if (is_array($j)) {
            foreach (['message', 'error', 'detail', 'error_description', 'code'] as $k) {
                if (!empty($j[$k]) && is_string($j[$k])) {
                    return mb_substr($j[$k], 0, 200);
                }
            }
        }
        $body = trim((string) ($resp['body'] ?? ''));
        return $body !== '' ? mb_substr($body, 0, 200) : '';
    }

    public function sync(array $params): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $link = EbAccountLink::find((int) ($params['id'] ?? 0));
        // Comprova pertinença a la llar.
        if ($link === null || Account::find((int) $link['account_id'], $hid) === null) {
            abort(404);
        }
        $link['household_id'] = $hid;
        $link['currency'] = Account::find((int) $link['account_id'], $hid)['currency'];

        try {
            [$new, $dup] = (new EbSyncService($this->service()))->syncLink($link);
            AuditLog::record('eb_sync', 'eb_account_link', (int) $link['id'], "new=$new dup=$dup");
            flash('eb_ok', __('eb.synced', ['new' => $new, 'dup' => $dup]));
        } catch (\Throwable $e) {
            flash('eb_error', $e->getMessage());
        }
        redirect('/banking');
    }

    /** @param array<int,mixed> $accounts */
    private function persistAccounts(array $accounts, int $sessionRowId, int $householdId): int
    {
        $count = 0;
        foreach ($accounts as $acc) {
            // Pot venir com a string (uid) o objecte amb detalls.
            $uid = is_string($acc) ? $acc : (string) ($acc['uid'] ?? ($acc['account_id']['iban'] ?? ''));
            if ($uid === '' || EbAccountLink::existsForUid($uid)) {
                continue;
            }
            $iban = is_array($acc) ? (string) ($acc['account_id']['iban'] ?? '') : '';
            $name = is_array($acc)
                ? (string) ($acc['name'] ?? ($acc['product'] ?? ($iban !== '' ? 'IBAN ' . substr($iban, -4) : 'Compte EB')))
                : 'Compte EB';
            $currency = is_array($acc) ? (string) ($acc['currency'] ?? 'EUR') : 'EUR';

            $accountId = Account::create($householdId, [
                'owner_user_id'   => Auth::id(),
                'name'            => mb_substr($name, 0, 191),
                'type'            => 'corrent',
                'currency'        => $currency,
                'opening_balance' => 0,
                'iban_last4'      => $iban !== '' ? substr($iban, -4) : null,
                'source'          => 'enablebanking',
            ]);
            EbAccountLink::create($accountId, $sessionRowId, $uid, $iban !== '' ? hash('sha256', $iban) : null);
            $count++;
        }
        return $count;
    }

    /** @param array<string,mixed> $json */
    private function parseValidUntil(array $json): ?string
    {
        $raw = $json['access']['valid_until'] ?? ($json['valid_until'] ?? null);
        if ($raw === null) {
            return null;
        }
        $ts = strtotime((string) $raw);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
