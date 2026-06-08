<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Goal;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class GoalsController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        View::render('goals/index', [
            'goals'    => Goal::allByHousehold($hid),
            'accounts' => Account::forSelect($hid),
            'ok'       => flash('goal_ok'),
            'error'    => flash('goal_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/goals');
        }
        $id = Goal::create($hid, $d);
        AuditLog::record('goal_created', 'goal', $id, $d['name']);
        flash('goal_ok', __('goal.created'));
        redirect('/goals');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Goal::find($id, $hid) === null) {
            abort(404);
        }
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/goals');
        }
        Goal::update($id, $hid, $d);
        AuditLog::record('goal_updated', 'goal', $id);
        flash('goal_ok', __('goal.updated'));
        redirect('/goals');
    }

    /** Afegeix una aportació al saldo actual de l'objectiu. @param array<string,string> $params */
    public function contribute(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        $goal = Goal::find($id, $hid);
        if ($goal === null) {
            abort(404);
        }
        $add = to_amount($_POST['amount'] ?? '0');
        $newCurrent = round((float) $goal['current_amount'] + $add, 2);
        Goal::update($id, $hid, [
            'name' => $goal['name'], 'target_amount' => $goal['target_amount'],
            'current_amount' => $newCurrent, 'target_date' => $goal['target_date'], 'account_id' => $goal['account_id'],
        ]);
        AuditLog::record('goal_contribution', 'goal', $id, (string) $add);
        flash('goal_ok', __('goal.updated'));
        redirect('/goals');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Goal::find($id, $hid) === null) {
            abort(404);
        }
        Goal::delete($id, $hid);
        AuditLog::record('goal_deleted', 'goal', $id);
        flash('goal_ok', __('goal.deleted'));
        redirect('/goals');
    }

    /** @return array<string,mixed>|null */
    private function fromRequest(int $hid): ?array
    {
        $name = trim($_POST['name'] ?? '');
        $target = to_amount($_POST['target_amount'] ?? '0');
        if ($name === '' || $target <= 0) {
            flash('goal_error', __('goal.invalid'));
            return null;
        }
        $accountId = (int) ($_POST['account_id'] ?? 0);
        if ($accountId > 0 && Account::find($accountId, $hid) === null) {
            $accountId = 0;
        }
        $date = trim($_POST['target_date'] ?? '');
        return [
            'name'           => mb_substr($name, 0, 191),
            'target_amount'  => $target,
            'current_amount' => to_amount($_POST['current_amount'] ?? '0'),
            'target_date'    => $date !== '' ? $date : null,
            'account_id'     => $accountId ?: null,
        ];
    }
}
