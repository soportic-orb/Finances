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

    /** @return array<string,mixed>|null */
    public static function find(int $id, int $householdId): ?array
    {
        $row = DB::run('SELECT * FROM categories WHERE id = ? AND household_id = ? LIMIT 1', [$id, $householdId])->fetch();
        return $row ?: null;
    }

    public const KINDS = ['ingres', 'despesa', 'traspas'];

    public static function create(int $householdId, ?int $parentId, string $name, string $kind, ?string $icon, ?string $color): int
    {
        DB::run(
            'INSERT INTO categories (household_id, parent_id, name, kind, icon, color) VALUES (?, ?, ?, ?, ?, ?)',
            [$householdId, $parentId, $name, $kind, $icon, $color]
        );
        return (int) DB::connection()->lastInsertId();
    }

    public static function update(int $id, int $householdId, ?int $parentId, string $name, string $kind, ?string $icon, ?string $color): void
    {
        // Evita que una categoria sigui pare d'ella mateixa.
        if ($parentId === $id) {
            $parentId = null;
        }
        DB::run(
            'UPDATE categories SET parent_id = ?, name = ?, kind = ?, icon = ?, color = ? WHERE id = ? AND household_id = ?',
            [$parentId, $name, $kind, $icon, $color, $id, $householdId]
        );
    }

    public static function delete(int $id, int $householdId): void
    {
        DB::run('DELETE FROM categories WHERE id = ? AND household_id = ?', [$id, $householdId]);
    }

    /** @return array<int,string> categories pare (sense parent) id => name, per a selects */
    public static function parentsForSelect(int $householdId): array
    {
        $rows = DB::run(
            'SELECT id, name FROM categories WHERE household_id = ? AND parent_id IS NULL ORDER BY name',
            [$householdId]
        )->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = (string) $r['name'];
        }
        return $out;
    }
}
