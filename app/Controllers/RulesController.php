<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Rule;
use App\Services\RuleEngine;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class RulesController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        View::render('rules/index', [
            'rules'      => Rule::allByHousehold($hid),
            'categories' => Category::flatLabels($hid),
            'matchTypes' => Rule::MATCH_TYPES,
            'fields'     => Rule::FIELDS,
            'ok'         => flash('rule_ok'),
            'error'      => flash('rule_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/rules');
        }
        $id = Rule::create($hid, $d);
        AuditLog::record('rule_created', 'rule', $id);
        flash('rule_ok', __('rule.created'));
        redirect('/rules');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $rule = Rule::find((int) ($params['id'] ?? 0), $hid);
        if ($rule === null) {
            abort(404);
        }
        View::render('rules/edit', [
            'rule'       => $rule,
            'categories' => Category::flatLabels($hid),
            'matchTypes' => Rule::MATCH_TYPES,
            'fields'     => Rule::FIELDS,
        ], 'layouts/app');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Rule::find($id, $hid) === null) {
            abort(404);
        }
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/rules/' . $id . '/edit');
        }
        Rule::update($id, $hid, $d);
        AuditLog::record('rule_updated', 'rule', $id);
        flash('rule_ok', __('rule.updated'));
        redirect('/rules');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Rule::find($id, $hid) === null) {
            abort(404);
        }
        Rule::delete($id, $hid);
        AuditLog::record('rule_deleted', 'rule', $id);
        flash('rule_ok', __('rule.deleted'));
        redirect('/rules');
    }

    /** Aplica les regles als moviments existents. */
    public function apply(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $mode = ($_POST['mode'] ?? 'uncategorized') === 'all' ? 'all' : 'uncategorized';
        $updated = RuleEngine::applyToHousehold($hid, $mode);
        AuditLog::record('rules_applied', 'rule', null, "mode=$mode updated=$updated");
        flash('rule_ok', __('rule.applied', ['n' => $updated]));
        redirect('/rules');
    }

    /** @return array<string,mixed>|null */
    private function fromRequest(int $hid): ?array
    {
        $matchType = $_POST['match_type'] ?? 'conte';
        $field = $_POST['field'] ?? 'description';
        $pattern = trim($_POST['pattern'] ?? '');
        $categoryId = (int) ($_POST['set_category_id'] ?? 0);

        if ($pattern === '' || !in_array($matchType, Rule::MATCH_TYPES, true)
            || !in_array($field, Rule::FIELDS, true) || !Category::exists($categoryId, $hid)) {
            flash('rule_error', __('rule.invalid'));
            return null;
        }
        // Valida regex abans de desar.
        if ($matchType === 'regex' && @preg_match('~' . str_replace('~', '\~', $pattern) . '~u', '') === false) {
            flash('rule_error', __('rule.bad_regex'));
            return null;
        }

        return [
            'match_type'      => $matchType,
            'field'           => $field,
            'pattern'         => mb_substr($pattern, 0, 255),
            'set_category_id' => $categoryId,
            'priority'        => (int) ($_POST['priority'] ?? 100),
            'enabled'         => isset($_POST['enabled']) ? 1 : 0,
        ];
    }
}
