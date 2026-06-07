<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\DB;

/**
 * Detecció de càrrecs periòdics (recurrents/subscripcions) a partir de l'històric.
 *
 * Agrupa despeses pel comerç (o descripció normalitzada), i si hi ha prou
 * repeticions a intervals regulars i imports estables, ho marca com a recurrent.
 */
final class RecurringDetector
{
    private const MIN_OCCURRENCES = 3;
    private const AMOUNT_TOLERANCE = 0.15; // 15%

    /**
     * @return array<int,array<string,mixed>> patrons detectats
     */
    public static function detect(int $householdId, int $monthsBack = 12): array
    {
        $from = date('Y-m-d', strtotime("-$monthsBack months"));
        $rows = DB::run(
            "SELECT occurred_on, amount, merchant, description
             FROM transactions
             WHERE household_id = ? AND type = 'expense' AND occurred_on >= ?
             ORDER BY occurred_on ASC",
            [$householdId, $from]
        )->fetchAll();

        // Agrupa per clau normalitzada.
        $groups = [];
        foreach ($rows as $r) {
            $key = self::normalizeKey((string) ($r['merchant'] ?? ''), (string) ($r['description'] ?? ''));
            if ($key === '') {
                continue;
            }
            $groups[$key][] = ['date' => (string) $r['occurred_on'], 'amount' => abs((float) $r['amount']), 'label' => self::label($r)];
        }

        $patterns = [];
        foreach ($groups as $items) {
            if (count($items) < self::MIN_OCCURRENCES) {
                continue;
            }
            $pattern = self::analyze($items);
            if ($pattern !== null) {
                $patterns[] = $pattern;
            }
        }

        // Ordena: subscripcions primer, després per import.
        usort($patterns, static fn ($a, $b) => [$b['is_subscription'], $b['amount_est']] <=> [$a['is_subscription'], $a['amount_est']]);
        return $patterns;
    }

    private static function normalizeKey(string $merchant, string $description): string
    {
        $base = $merchant !== '' ? $merchant : $description;
        $base = mb_strtolower($base);
        $base = preg_replace('/\d+/', '', $base) ?? '';        // treu números
        $base = preg_replace('/[^a-z\s]/u', ' ', $base) ?? ''; // treu símbols
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? '');
        return mb_substr($base, 0, 40);
    }

    /** @param array<int,array<string,mixed>> $r */
    private static function label(array $r): string
    {
        $l = trim((string) ($r['merchant'] ?? '')) ?: trim((string) ($r['description'] ?? ''));
        return mb_substr($l, 0, 191);
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<string,mixed>|null
     */
    private static function analyze(array $items): ?array
    {
        usort($items, static fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));

        // Intervals en dies entre ocurrències consecutives.
        $intervals = [];
        for ($i = 1; $i < count($items); $i++) {
            $d = (strtotime((string) $items[$i]['date']) - strtotime((string) $items[$i - 1]['date'])) / 86400;
            if ($d > 0) {
                $intervals[] = $d;
            }
        }
        if ($intervals === []) {
            return null;
        }
        sort($intervals);
        $median = $intervals[intdiv(count($intervals), 2)];

        $cadence = self::cadence($median);
        if ($cadence === null) {
            return null;
        }

        // Estabilitat d'imports.
        $amounts = array_map(static fn ($it) => (float) $it['amount'], $items);
        $avg = array_sum($amounts) / count($amounts);
        if ($avg <= 0) {
            return null;
        }
        $maxDev = 0.0;
        foreach ($amounts as $a) {
            $maxDev = max($maxDev, abs($a - $avg) / $avg);
        }
        $stable = $maxDev <= self::AMOUNT_TOLERANCE;

        $last = (string) end($items)['date'];
        $nextExpected = date('Y-m-d', strtotime($last . ' +' . (int) round($median) . ' days'));
        $graceDays = (int) round($median) + 10;
        $status = strtotime($nextExpected) < strtotime('-10 days') || strtotime($last) < strtotime("-$graceDays days")
            ? 'inactive' : 'active';

        return [
            'label'            => (string) $items[0]['label'],
            'amount_est'       => round($avg, 2),
            'cadence'          => $cadence,
            'next_expected_on' => $nextExpected,
            'last_seen_on'     => $last,
            'occurrences'      => count($items),
            'is_subscription'  => ($stable && in_array($cadence, ['mensual', 'anual', 'trimestral'], true)) ? 1 : 0,
            'status'           => $status,
        ];
    }

    private static function cadence(float $days): ?string
    {
        return match (true) {
            $days >= 5 && $days <= 9    => 'setmanal',
            $days >= 12 && $days <= 18  => 'quinzenal',
            $days >= 26 && $days <= 35  => 'mensual',
            $days >= 58 && $days <= 70  => 'bimensual',
            $days >= 85 && $days <= 100 => 'trimestral',
            $days >= 170 && $days <= 195 => 'semestral',
            $days >= 350 && $days <= 380 => 'anual',
            default => null,
        };
    }
}
