<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Rule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RuleEngine;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class TransactionsController
{
    private const PER_PAGE = 50;

    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        $filters = $this->filtersFromQuery();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $total = Transaction::count($hid, $filters);
        $rows = Transaction::query($hid, $filters, self::PER_PAGE, $offset);

        View::render('transactions/index', [
            'rows'       => $rows,
            'total'      => $total,
            'sum'        => Transaction::sum($hid, $filters),
            'page'       => $page,
            'pages'      => max(1, (int) ceil($total / self::PER_PAGE)),
            'filters'    => $filters,
            'accounts'   => Account::forSelect($hid),
            'categories' => Category::flatLabels($hid),
            'members'    => User::allByHousehold($hid),
            'ok'         => flash('tx_ok'),
            'error'      => flash('tx_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $account = Account::find($accountId, $hid);
        $type = $_POST['type'] ?? 'expense';
        $amountInput = to_amount($_POST['amount'] ?? '0');
        $date = $this->validDate($_POST['occurred_on'] ?? '');

        if ($account === null || !in_array($type, ['income', 'expense'], true) || $amountInput <= 0 || $date === null) {
            flash('tx_error', __('tx.invalid'));
            redirect('/transactions');
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId > 0 && !Category::exists($categoryId, $hid)) {
            $categoryId = 0;
        }

        // Signe segons el tipus.
        $signed = $type === 'expense' ? -$amountInput : $amountInput;

        // Categorització per regles en ingesta si l'usuari no ha triat categoria.
        if ($categoryId === 0) {
            $fromRule = RuleEngine::categoryFor(Rule::enabledByHousehold($hid), [
                'description'  => trim($_POST['description'] ?? ''),
                'merchant'     => trim($_POST['merchant'] ?? ''),
                'counterparty' => '',
                'amount'       => $signed,
            ]);
            if ($fromRule !== null) {
                $categoryId = $fromRule;
            }
        }

        $id = Transaction::create($hid, [
            'account_id'        => $accountId,
            'category_id'       => $categoryId ?: null,
            'type'              => $type,
            'amount'            => $signed,
            'currency'          => $account['currency'],
            'occurred_on'       => $date,
            'value_date'        => $this->validDate($_POST['value_date'] ?? ''),
            'description'       => trim($_POST['description'] ?? '') ?: null,
            'merchant'          => trim($_POST['merchant'] ?? '') ?: null,
            'counterparty'      => null,
            'notes'             => trim($_POST['notes'] ?? '') ?: null,
            'transfer_group_id' => null,
            'source'            => 'manual',
        ]);
        Account::recalc($accountId, $hid);
        AuditLog::record('transaction_created', 'transaction', $id);
        flash('tx_ok', __('tx.created'));
        redirect('/transactions');
    }

    public function transfer(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        $from = (int) ($_POST['from_account'] ?? 0);
        $to = (int) ($_POST['to_account'] ?? 0);
        $amount = to_amount($_POST['amount'] ?? '0');
        $date = $this->validDate($_POST['occurred_on'] ?? '');

        $fromAcc = Account::find($from, $hid);
        $toAcc = Account::find($to, $hid);

        if ($fromAcc === null || $toAcc === null || $from === $to || $amount <= 0 || $date === null) {
            flash('tx_error', __('tx.transfer_invalid'));
            redirect('/transactions');
        }

        $group = Transaction::createTransfer(
            $hid, $from, $to, $amount, $fromAcc['currency'], $date,
            trim($_POST['description'] ?? '') ?: __('tx.transfer'),
            trim($_POST['notes'] ?? '') ?: null
        );
        Account::recalc($from, $hid);
        Account::recalc($to, $hid);
        AuditLog::record('transfer_created', 'transfer', null, $group);
        flash('tx_ok', __('tx.transfer_done'));
        redirect('/transactions');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $tx = Transaction::find((int) ($params['id'] ?? 0), $hid);
        if ($tx === null) {
            abort(404);
        }
        View::render('transactions/edit', [
            'tx'         => $tx,
            'categories' => Category::flatLabels($hid),
        ], 'layouts/app');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        $tx = Transaction::find($id, $hid);
        if ($tx === null) {
            abort(404);
        }

        $type = $tx['type'] === 'transfer' ? 'transfer' : ($_POST['type'] ?? $tx['type']);
        $amountInput = to_amount($_POST['amount'] ?? '0');
        $date = $this->validDate($_POST['occurred_on'] ?? '');
        if ($amountInput <= 0 || $date === null || ($type !== 'transfer' && !in_array($type, ['income', 'expense'], true))) {
            flash('tx_error', __('tx.invalid'));
            redirect('/transactions/' . $id . '/edit');
        }

        // Conserva el signe original per a traspassos; recalcula per a ingrés/despesa.
        if ($type === 'transfer') {
            $signed = ((float) $tx['amount'] < 0 ? -1 : 1) * $amountInput;
        } else {
            $signed = $type === 'expense' ? -$amountInput : $amountInput;
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId > 0 && !Category::exists($categoryId, $hid)) {
            $categoryId = 0;
        }

        Transaction::update($id, $hid, [
            'category_id' => $categoryId ?: null,
            'type'        => $type,
            'amount'      => $signed,
            'occurred_on' => $date,
            'value_date'  => $this->validDate($_POST['value_date'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'merchant'    => trim($_POST['merchant'] ?? '') ?: null,
            'counterparty' => $tx['counterparty'],
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Account::recalc((int) $tx['account_id'], $hid);
        AuditLog::record('transaction_updated', 'transaction', $id);
        flash('tx_ok', __('tx.updated'));
        redirect('/transactions');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        $tx = Transaction::find($id, $hid);
        if ($tx === null) {
            abort(404);
        }

        if (!empty($tx['transfer_group_id'])) {
            $affected = Transaction::accountsInGroup($tx['transfer_group_id'], $hid);
            Transaction::deleteGroup($tx['transfer_group_id'], $hid);
            foreach ($affected as $accId) {
                Account::recalc($accId, $hid);
            }
            AuditLog::record('transfer_deleted', 'transfer', null, $tx['transfer_group_id']);
        } else {
            Transaction::delete($id, $hid);
            Account::recalc((int) $tx['account_id'], $hid);
            AuditLog::record('transaction_deleted', 'transaction', $id);
        }
        flash('tx_ok', __('tx.deleted'));
        redirect('/transactions');
    }

    /** @return array<string,mixed> */
    private function filtersFromQuery(): array
    {
        return [
            'account'  => (int) ($_GET['account'] ?? 0) ?: null,
            'category' => (int) ($_GET['category'] ?? 0) ?: null,
            'member'   => (int) ($_GET['member'] ?? 0) ?: null,
            'type'     => $_GET['type'] ?? null,
            'q'        => trim($_GET['q'] ?? ''),
            'from'     => $this->validDate($_GET['from'] ?? ''),
            'to'       => $this->validDate($_GET['to'] ?? ''),
            'min'      => isset($_GET['min']) && $_GET['min'] !== '' ? (string) to_amount($_GET['min']) : '',
            'max'      => isset($_GET['max']) && $_GET['max'] !== '' ? (string) to_amount($_GET['max']) : '',
        ];
    }

    private function validDate(string $date): ?string
    {
        $date = trim($date);
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return ($d && $d->format('Y-m-d') === $date) ? $date : null;
    }
}
