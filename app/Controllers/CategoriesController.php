<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class CategoriesController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        View::render('categories/index', [
            'tree'    => Category::tree($hid),
            'parents' => Category::parentsForSelect($hid),
            'kinds'   => Category::KINDS,
            'ok'      => flash('cat_ok'),
            'error'   => flash('cat_error'),
        ], 'layouts/app');
    }

    public function store(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/categories');
        }
        $id = Category::create($hid, $d['parent_id'], $d['name'], $d['kind'], $d['icon'], $d['color']);
        AuditLog::record('category_created', 'category', $id, $d['name']);
        flash('cat_ok', __('cat.created'));
        redirect('/categories');
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $cat = Category::find((int) ($params['id'] ?? 0), $hid);
        if ($cat === null) {
            abort(404);
        }
        View::render('categories/edit', [
            'cat'     => $cat,
            'parents' => Category::parentsForSelect($hid),
            'kinds'   => Category::KINDS,
        ], 'layouts/app');
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Category::find($id, $hid) === null) {
            abort(404);
        }
        $d = $this->fromRequest($hid);
        if ($d === null) {
            redirect('/categories/' . $id . '/edit');
        }
        Category::update($id, $hid, $d['parent_id'], $d['name'], $d['kind'], $d['icon'], $d['color']);
        AuditLog::record('category_updated', 'category', $id, $d['name']);
        flash('cat_ok', __('cat.updated'));
        redirect('/categories');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $id = (int) ($params['id'] ?? 0);
        if (Category::find($id, $hid) === null) {
            abort(404);
        }
        Category::delete($id, $hid);
        AuditLog::record('category_deleted', 'category', $id);
        flash('cat_ok', __('cat.deleted'));
        redirect('/categories');
    }

    /** @return array<string,mixed>|null */
    private function fromRequest(int $hid): ?array
    {
        $name = trim($_POST['name'] ?? '');
        $kind = $_POST['kind'] ?? 'despesa';
        if ($name === '' || !in_array($kind, Category::KINDS, true)) {
            flash('cat_error', __('cat.invalid'));
            return null;
        }
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        if ($parentId > 0 && !Category::exists($parentId, $hid)) {
            $parentId = 0;
        }
        $color = trim($_POST['color'] ?? '');
        return [
            'name'      => mb_substr($name, 0, 191),
            'kind'      => $kind,
            'parent_id' => $parentId ?: null,
            'icon'      => trim($_POST['icon'] ?? '') ?: null,
            'color'     => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : null,
        ];
    }
}
