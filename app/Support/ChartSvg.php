<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Generació de gràfics com a SVG al servidor (autoallotjats, sense JS).
 * Funcionen offline i s'incrusten directament als informes PDF.
 */
final class ChartSvg
{
    private const PALETTE = ['#4f8cff', '#ef4444', '#10b981', '#f59e0b', '#a855f7', '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#64748b'];

    /**
     * Gràfic de formatge (donut).
     * @param array<int,array{label:string,value:float,color?:?string}> $data
     */
    public static function donut(array $data, int $size = 200): string
    {
        $total = 0.0;
        foreach ($data as $d) {
            $total += max(0, (float) $d['value']);
        }
        $r = 70;
        $cx = $size / 2;
        $cy = $size / 2;
        $circ = 2 * M_PI * $r;
        $sw = 30;

        $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" class="chart chart--donut" role="img">';
        if ($total <= 0) {
            $svg .= self::circle($cx, $cy, $r, '#2a2f3a', $sw);
            $svg .= '</svg>';
            return $svg;
        }

        $offset = 0.0;
        $i = 0;
        $svg .= self::circle($cx, $cy, $r, '#2a2f3a', $sw); // pista de fons
        foreach ($data as $d) {
            $val = max(0, (float) $d['value']);
            if ($val <= 0) {
                continue;
            }
            $len = $val / $total * $circ;
            $color = $d['color'] ?? self::PALETTE[$i % count(self::PALETTE)];
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . e($color) . '" '
                . 'stroke-width="' . $sw . '" stroke-dasharray="' . self::n($len) . ' ' . self::n($circ - $len) . '" '
                . 'stroke-dashoffset="' . self::n(-$offset) . '" transform="rotate(-90 ' . $cx . ' ' . $cy . ')" />';
            $offset += $len;
            $i++;
        }
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Barres agrupades (ingressos vs despeses per mes).
     * @param array<int,array{label:string,income:float,expense:float}> $months
     */
    public static function bars(array $months, int $width = 460, int $height = 180): string
    {
        $max = 0.0;
        foreach ($months as $m) {
            $max = max($max, (float) $m['income'], (float) $m['expense']);
        }
        $max = $max > 0 ? $max : 1;
        $pad = 24;
        $chartH = $height - $pad - 16;
        $n = max(1, count($months));
        $groupW = ($width - $pad) / $n;
        $barW = max(3, $groupW / 3);

        $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="chart chart--bars" role="img">';
        $svg .= '<line x1="' . $pad . '" y1="' . ($chartH + $pad) . '" x2="' . $width . '" y2="' . ($chartH + $pad) . '" stroke="#2a2f3a"/>';
        $x = $pad + 4;
        foreach ($months as $m) {
            $ih = (float) $m['income'] / $max * $chartH;
            $eh = (float) $m['expense'] / $max * $chartH;
            $base = $chartH + $pad;
            $svg .= '<rect x="' . self::n($x) . '" y="' . self::n($base - $ih) . '" width="' . self::n($barW) . '" height="' . self::n($ih) . '" fill="#10b981"/>';
            $svg .= '<rect x="' . self::n($x + $barW + 2) . '" y="' . self::n($base - $eh) . '" width="' . self::n($barW) . '" height="' . self::n($eh) . '" fill="#ef4444"/>';
            $svg .= '<text x="' . self::n($x + $barW) . '" y="' . ($height - 2) . '" fill="#9aa3b2" font-size="9" text-anchor="middle">' . e((string) $m['label']) . '</text>';
            $x += $groupW;
        }
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Línia (patrimoni net en el temps).
     * @param array<int,array{label:string,value:float}> $points
     */
    public static function line(array $points, int $width = 460, int $height = 180): string
    {
        if ($points === []) {
            return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="chart"></svg>';
        }
        $values = array_map(static fn ($p) => (float) $p['value'], $points);
        $min = min($values);
        $max = max($values);
        $range = ($max - $min) > 0 ? ($max - $min) : 1;
        $pad = 28;
        $chartH = $height - $pad - 16;
        $chartW = $width - $pad - 8;
        $n = max(1, count($points) - 1);
        $step = $chartW / $n;

        $coords = [];
        foreach ($points as $i => $p) {
            $x = $pad + $i * $step;
            $y = $pad + $chartH - (((float) $p['value'] - $min) / $range * $chartH);
            $coords[] = self::n($x) . ',' . self::n($y);
        }

        $svg = '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="chart chart--line" role="img">';
        $svg .= '<line x1="' . $pad . '" y1="' . ($chartH + $pad) . '" x2="' . $width . '" y2="' . ($chartH + $pad) . '" stroke="#2a2f3a"/>';
        $svg .= '<polyline fill="none" stroke="#4f8cff" stroke-width="2" points="' . implode(' ', $coords) . '"/>';
        foreach ($points as $i => $p) {
            [$cx, $cy] = explode(',', $coords[$i]);
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="2.5" fill="#4f8cff"/>';
            if ($i % max(1, intdiv(count($points), 6)) === 0) {
                $svg .= '<text x="' . $cx . '" y="' . ($height - 2) . '" fill="#9aa3b2" font-size="9" text-anchor="middle">' . e((string) $p['label']) . '</text>';
            }
        }
        $svg .= '</svg>';
        return $svg;
    }

    private static function circle(float $cx, float $cy, float $r, string $color, float $sw): string
    {
        return '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="' . $color . '" stroke-width="' . $sw . '"/>';
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    /** @return string[] paleta de colors per a llegendes */
    public static function palette(): array
    {
        return self::PALETTE;
    }
}
