<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

/**
 * Càlcul de progrés de pressupostos amb llindars d'alerta i rollover.
 */
final class BudgetService
{
    public const WARN = 80.0;
    public const OVER = 100.0;

    /**
     * Progrés d'un pressupost en el període actual.
     * @param array<string,mixed> $budget
     * @return array{spent:float,amount:float,base_amount:float,rollover_in:float,pct:float,status:string,from:string,to:string}
     */
    public static function progress(array $budget, ?string $refDate = null): array
    {
        $ref = $refDate ?? date('Y-m-d');
        [$from, $to] = self::window((string) $budget['period'], $ref);

        $categoryIds = self::categoryIds((int) $budget['household_id'], (int) $budget['category_id']);
        $spent = self::spent((int) $budget['household_id'], $categoryIds, $from, $to);

        $base = (float) $budget['amount'];
        $rolloverIn = 0.0;
        if ((int) $budget['rollover'] === 1) {
            [$pFrom, $pTo] = self::previousWindow((string) $budget['period'], $ref);
            $prevSpent = self::spent((int) $budget['household_id'], $categoryIds, $pFrom, $pTo);
            $rolloverIn = max(0.0, $base - $prevSpent);
        }
        $amount = round($base + $rolloverIn, 2);

        $pct = $amount > 0 ? round($spent / $amount * 100, 1) : 0.0;
        $status = $pct >= self::OVER ? 'over' : ($pct >= self::WARN ? 'warn' : 'ok');

        return [
            'spent' => round($spent, 2), 'amount' => $amount, 'base_amount' => $base,
            'rollover_in' => round($rolloverIn, 2), 'pct' => $pct, 'status' => $status,
            'from' => $from, 'to' => $to,
        ];
    }

    /** @return array{0:string,1:string} */
    public static function window(string $period, string $ref): array
    {
        $t = strtotime($ref);
        if ($period === 'anual') {
            return [date('Y-01-01', $t), date('Y-12-31', $t)];
        }
        return [date('Y-m-01', $t), date('Y-m-t', $t)];
    }

    /** @return array{0:string,1:string} */
    private static function previousWindow(string $period, string $ref): array
    {
        $t = strtotime($ref);
        if ($period === 'anual') {
            $prev = strtotime('-1 year', $t);
            return [date('Y-01-01', $prev), date('Y-12-31', $prev)];
        }
        $prev = strtotime('first day of -1 month', $t);
        return [date('Y-m-01', $prev), date('Y-m-t', $prev)];
    }

    /** @return array<int,int> categoria + subcategories directes */
    private static function categoryIds(int $householdId, int $categoryId): array
    {
        $rows = DB::run(
            'SELECT id FROM categories WHERE household_id = ? AND (id = ? OR parent_id = ?)',
            [$householdId, $categoryId, $categoryId]
        )->fetchAll();
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        return $ids === [] ? [$categoryId] : $ids;
    }

    /** @param array<int,int> $categoryIds */
    private static function spent(int $householdId, array $categoryIds, string $from, string $to): float
    {
        $place = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = array_merge([$householdId], $categoryIds, [$from, $to]);
        $row = DB::run(
            "SELECT COALESCE(SUM(ABS(amount)),0) AS s
             FROM transactions
             WHERE household_id = ? AND type = 'expense' AND category_id IN ($place)
               AND occurred_on BETWEEN ? AND ?",
            $params
        )->fetch();
        return (float) $row['s'];
    }
}
