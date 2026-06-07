<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class AccountsController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        View::render('accounts/index', [
            'accounts' => Account::allByHousehold($hid, true),
            'netWorth' => Account::netWorth($hid),
            'members'  => User::allByHousehold($hid),
            'types'    => Account::TYPES,
            'ok'       => flash('acc_ok'),
            'error'    => flash('acc_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        $d = $this->fromRequest();
        if ($d === null) {
            redirect('/accounts');
        }

        $id = Account::create($hid, $d);
        AuditLog::record('account_created', 'account', $id, $d['name']);
        flash('acc_ok', __('acc.created'));
        redirect('/accounts');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $account = Account::find((int) ($params['id'] ?? 0), $hid);
        if ($account === null) {
            abort(404);
        }
        View::render('accounts/edit', [
            'account' => $account,
            'members' => User::allByHousehold($hid),
            'types'   => Account::TYPES,
        ], 'layouts/app');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Account::find($id, $hid) === null) {
            abort(404);
        }
        $d = $this->fromRequest();
        if ($d === null) {
            redirect('/accounts/' . $id . '/edit');
        }
        Account::update($id, $hid, $d);
        AuditLog::record('account_updated', 'account', $id, $d['name']);
        flash('acc_ok', __('acc.updated'));
        redirect('/accounts');
    }

    /** @param array<string,string> $params */
    public function archive(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        $account = Account::find($id, $hid);
        if ($account === null) {
            abort(404);
        }
        $newState = !((int) $account['archived'] === 1);
        Account::setArchived($id, $hid, $newState);
        AuditLog::record($newState ? 'account_archived' : 'account_unarchived', 'account', $id);
        flash('acc_ok', __('acc.updated'));
        redirect('/accounts');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Account::find($id, $hid) === null) {
            abort(404);
        }
        Account::delete($id, $hid);
        AuditLog::record('account_deleted', 'account', $id);
        flash('acc_ok', __('acc.deleted'));
        redirect('/accounts');
    }

    /** Valida i normalitza l'entrada; retorna null si no és vàlida. @return array<string,mixed>|null */
    private function fromRequest(): ?array
    {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'corrent';
        if ($name === '' || !in_array($type, Account::TYPES, true)) {
            flash('acc_error', __('acc.invalid'));
            return null;
        }
        $owner = (int) ($_POST['owner_user_id'] ?? 0);
        // El membre només pot assignar-se comptes a si mateix.
        if (!Auth::isOwner()) {
            $owner = (int) Auth::id();
        }
        $ibanDigits = preg_replace('/\D/', '', $_POST['iban_last4'] ?? '') ?? '';
        return [
            'name'            => mb_substr($name, 0, 191),
            'type'            => $type,
            'currency'        => strtoupper(substr(trim($_POST['currency'] ?? 'EUR'), 0, 3)),
            'opening_balance' => to_amount($_POST['opening_balance'] ?? '0'),
            'iban_last4'      => $ibanDigits !== '' ? substr($ibanDigits, -4) : null,
            'owner_user_id'   => $owner ?: null,
            'source'          => 'manual',
        ];
    }
}
