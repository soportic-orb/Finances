<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

/**
 * Càlcul d'agregats per al dashboard i els informes.
 */
final class ReportService
{
    /**
     * Resum d'un mes: ingressos, despeses, net i taxa d'estalvi.
     * @return array{income:float,expense:float,net:float,savings_rate:float,from:string,to:string}
     */
    public static function monthlySummary(int $hid, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));
        $row = DB::run(
            "SELECT
                COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS income,
                COALESCE(SUM(CASE WHEN type='expense' THEN ABS(amount) ELSE 0 END),0) AS expense
             FROM transactions
             WHERE household_id = ? AND occurred_on BETWEEN ? AND ?",
            [$hid, $from, $to]
        )->fetch();
        $income = (float) $row['income'];
        $expense = (float) $row['expense'];
        $net = round($income - $expense, 2);
        $rate = $income > 0 ? round($net / $income * 100, 1) : 0.0;
        return ['income' => $income, 'expense' => $expense, 'net' => $net, 'savings_rate' => $rate, 'from' => $from, 'to' => $to];
    }

    /**
     * Desglossament de despesa per categoria en un rang.
     * @return array<int,array{label:string,value:float,color:?string}>
     */
    public static function categoryBreakdown(int $hid, string $from, string $to, int $limit = 10): array
    {
        $rows = DB::run(
            "SELECT COALESCE(c.name, 'Sense categoria') AS label, c.color AS color,
                    SUM(ABS(t.amount)) AS value
             FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.household_id = ? AND t.type = 'expense' AND t.occurred_on BETWEEN ? AND ?
             GROUP BY t.category_id, c.name, c.color
             ORDER BY value DESC",
            [$hid, $from, $to]
        )->fetchAll();

        $out = [];
        foreach ($rows as $i => $r) {
            if ($i < $limit) {
                $out[] = ['label' => (string) $r['label'], 'value' => (float) $r['value'], 'color' => $r['color']];
            } else {
                $out[$limit - 1]['label'] = 'Altres';
                $out[$limit - 1]['value'] += (float) $r['value'];
                $out[$limit - 1]['color'] = '#64748b';
            }
        }
        return $out;
    }

    /**
     * Despesa per membre (propietari del compte) en un rang.
     * @return array<int,array{label:string,value:float}>
     */
    public static function byMember(int $hid, string $from, string $to): array
    {
        $rows = DB::run(
            "SELECT COALESCE(u.name, 'Sense assignar') AS label, SUM(ABS(t.amount)) AS value
             FROM transactions t
             JOIN accounts a ON a.id = t.account_id
             LEFT JOIN users u ON u.id = a.owner_user_id
             WHERE t.household_id = ? AND t.type = 'expense' AND t.occurred_on BETWEEN ? AND ?
             GROUP BY a.owner_user_id, u.name
             ORDER BY value DESC",
            [$hid, $from, $to]
        )->fetchAll();
        return array_map(static fn ($r) => ['label' => (string) $r['label'], 'value' => (float) $r['value']], $rows);
    }

    /**
     * Evolució mensual (ingressos/despeses) dels darrers N mesos.
     * @return array<int,array{label:string,income:float,expense:float}>
     */
    public static function monthlyEvolution(int $hid, int $monthsBack = 12): array
    {
        $out = [];
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $t = strtotime("first day of -$i month");
            $from = date('Y-m-01', $t);
            $to = date('Y-m-t', $t);
            $row = DB::run(
                "SELECT
                    COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS income,
                    COALESCE(SUM(CASE WHEN type='expense' THEN ABS(amount) ELSE 0 END),0) AS expense
                 FROM transactions WHERE household_id = ? AND occurred_on BETWEEN ? AND ?",
                [$hid, $from, $to]
            )->fetch();
            $out[] = ['label' => date('m/y', $t), 'income' => (float) $row['income'], 'expense' => (float) $row['expense']];
        }
        return $out;
    }

    /**
     * Patrimoni net a final de cada mes (darrers N mesos).
     * @return array<int,array{label:string,value:float}>
     */
    public static function netWorthSeries(int $hid, int $monthsBack = 12): array
    {
        $opening = (float) DB::run(
            'SELECT COALESCE(SUM(opening_balance),0) AS o FROM accounts WHERE household_id = ? AND archived = 0',
            [$hid]
        )->fetch()['o'];

        $out = [];
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $t = strtotime("first day of -$i month");
            $monthEnd = date('Y-m-t', $t);
            $cum = (float) DB::run(
                'SELECT COALESCE(SUM(t.amount),0) AS s
                 FROM transactions t JOIN accounts a ON a.id = t.account_id
                 WHERE a.household_id = ? AND a.archived = 0 AND t.occurred_on <= ?',
                [$hid, $monthEnd]
            )->fetch()['s'];
            $out[] = ['label' => date('m/y', $t), 'value' => round($opening + $cum, 2)];
        }
        return $out;
    }
}
