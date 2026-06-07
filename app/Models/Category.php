<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\DB;

final class Category
{
    /** @return array<int,array<string,mixed>> arbre: pares amb 'children' */
    public static function tree(int $householdId): array
    {
        $rows = DB::run(
            'SELECT id, parent_id, name, kind, icon, color FROM categories WHERE household_id = ? ORDER BY name',
            [$householdId]
        )->fetchAll();

        $byId = [];
        foreach ($rows as $r) {
            $r['children'] = [];
            $byId[(int) $r['id']] = $r;
        }
        $tree = [];
        foreach ($byId as $id => $r) {
            if ($r['parent_id'] !== null && isset($byId[(int) $r['parent_id']])) {
                $byId[(int) $r['parent_id']]['children'][] = &$byId[$id];
            } else {
                $tree[] = &$byId[$id];
            }
        }
        return $tree;
    }

    /** @return array<int,string> id => "Pare · Fill" per a etiquetes planes */
    public static function flatLabels(int $householdId): array
    {
        $rows = DB::run(
            'SELECT c.id, c.name, p.name AS parent_name
             FROM categories c LEFT JOIN categories p ON p.id = c.parent_id
             WHERE c.household_id = ? ORDER BY COALESCE(p.name, c.name), c.parent_id IS NOT NULL, c.name',
            [$householdId]
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $label = $r['parent_name'] !== null ? $r['parent_name'] . ' · ' . $r['name'] : $r['name'];
            $out[(int) $r['id']] = $label;
        }
        return $out;
    }

    public static function exists(int $id, int $householdId): bool
    {
        $row = DB::run('SELECT id FROM categories WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return (bool) $row;
    }
}
