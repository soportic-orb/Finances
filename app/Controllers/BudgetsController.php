<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class BudgetsController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $budgets = Budget::allByHousehold($hid);
        $rows = [];
        foreach ($budgets as $b) {
            $rows[] = ['budget' => $b, 'progress' => BudgetService::progress($b)];
        }
        View::render('budgets/index', [
            'rows'       => $rows,
            'categories' => Category::flatLabels($hid),
            'ok'         => flash('bud_ok'),
            'error'      => flash('bud_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/budgets');
        }
        $id = Budget::create($hid, $d);
        AuditLog::record('budget_created', 'budget', $id);
        flash('bud_ok', __('bud.created'));
        redirect('/budgets');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Budget::find($id, $hid) === null) {
            abort(404);
        }
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/budgets');
        }
        Budget::update($id, $hid, $d);
        AuditLog::record('budget_updated', 'budget', $id);
        flash('bud_ok', __('bud.updated'));
        redirect('/budgets');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Budget::find($id, $hid) === null) {
            abort(404);
        }
        Budget::delete($id, $hid);
        AuditLog::record('budget_deleted', 'budget', $id);
        flash('bud_ok', __('bud.deleted'));
        redirect('/budgets');
    }

    /** @return array<string,mixed>|null */
    private function fromRequest(int $hid): ?array
    {
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $period = in_array($_POST['period'] ?? '', ['mensual', 'anual'], true) ? $_POST['period'] : 'mensual';
        $amount = to_amount($_POST['amount'] ?? '0');
        if (!Category::exists($categoryId, $hid) || $amount <= 0) {
            flash('bud_error', __('bud.invalid'));
            return null;
        }
        $start = trim($_POST['start_on'] ?? '');
        return [
            'category_id' => $categoryId,
            'period'      => $period,
            'amount'      => $amount,
            'start_on'    => $start !== '' ? $start : null,
            'rollover'    => isset($_POST['rollover']) ? 1 : 0,
        ];
    }
}
