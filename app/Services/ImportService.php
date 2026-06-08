<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Importació d'extractes: Norma 43 (Quadern 43 AEB/CSB) i CSV amb mapatge.
 *
 * Cada parser retorna una llista de moviments normalitzats:
 *   ['occurred_on'=>'Y-m-d', 'value_date'=>?'Y-m-d', 'amount'=>float (signe),
 *    'description'=>?string, 'reference'=>?string]
 */
final class ImportService
{
    /**
     * Analitza un fitxer Norma 43 (registres de 80 posicions).
     * Registres: 11 (capçalera compte), 22 (moviment), 23 (concepte), 33/88 (fi).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function parseNorma43(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_filter(explode("\n", $content), static fn ($l) => trim($l) !== '');

        $movements = [];
        $current = null;

        foreach ($lines as $line) {
            $type = substr($line, 0, 2);

            if ($type === '22') {
                if ($current !== null) {
                    $movements[] = $current;
                }
                $dateOp = self::n43Date(substr($line, 6, 6));
                $dateVal = self::n43Date(substr($line, 12, 6));
                $sign = substr($line, 23, 1) === '1' ? -1 : 1; // 1=Debe (càrrec), 2=Haver (abonament)
                $importe = (float) substr($line, 24, 14) / 100.0;
                $doc = trim(substr($line, 38, 10));
                $ref1 = trim(substr($line, 48, 12));
                $ref2 = trim(substr($line, 60, 16));

                $current = [
                    'occurred_on' => $dateOp,
                    'value_date'  => $dateVal,
                    'amount'      => round($sign * $importe, 2),
                    'description' => '',
                    'reference'   => $ref2 !== '' ? $ref2 : ($ref1 !== '' ? $ref1 : ($doc !== '' ? $doc : null)),
                ];
            } elseif ($type === '23' && $current !== null) {
                // Concepte complementari (text lliure a partir de la posició 5).
                $concept = trim(substr($line, 4));
                if ($concept !== '') {
                    $current['description'] = trim(($current['description'] ?? '') . ' ' . $concept);
                }
            } elseif ($type === '33' || $type === '88') {
                if ($current !== null) {
                    $movements[] = $current;
                    $current = null;
                }
            }
        }
        if ($current !== null) {
            $movements[] = $current;
        }

        // Neteja descripcions buides.
        foreach ($movements as &$m) {
            $m['description'] = ($m['description'] ?? '') !== '' ? $m['description'] : null;
        }
        unset($m);

        return $movements;
    }

    private static function n43Date(string $yymmdd): ?string
    {
        $yymmdd = trim($yymmdd);
        if (!preg_match('/^\d{6}$/', $yymmdd)) {
            return null;
        }
        $yy = (int) substr($yymmdd, 0, 2);
        $mm = substr($yymmdd, 2, 2);
        $dd = substr($yymmdd, 4, 2);
        $year = $yy <= 79 ? 2000 + $yy : 1900 + $yy;
        if (!checkdate((int) $mm, (int) $dd, $year)) {
            return null;
        }
        return sprintf('%04d-%s-%s', $year, $mm, $dd);
    }

    /**
     * Analitza un CSV segons el mapatge donat.
     *
     * $map: delimiter, has_header(bool), decimal(','|'.'), date_format,
     *       date_col(int), amount_mode('single'|'debit_credit'),
     *       amount_col, debit_col, credit_col, desc_col, merchant_col, value_col
     *
     * @param array<string,mixed> $map
     * @return array<int,array<string,mixed>>
     */
    public static function parseCsv(string $content, array $map): array
    {
        $delimiter = $map['delimiter'] ?? ',';
        if ($delimiter === 'tab') {
            $delimiter = "\t";
        }
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_values(array_filter(explode("\n", $content), static fn ($l) => trim($l) !== ''));

        if (!empty($map['has_header']) && $lines !== []) {
            array_shift($lines);
        }

        $movements = [];
        foreach ($lines as $line) {
            $cols = str_getcsv($line, $delimiter);
            $date = self::parseDate((string) ($cols[$map['date_col']] ?? ''), (string) ($map['date_format'] ?? 'Y-m-d'));
            if ($date === null) {
                continue; // fila no vàlida
            }

            if (($map['amount_mode'] ?? 'single') === 'debit_credit') {
                $debit = self::parseAmount((string) ($cols[$map['debit_col']] ?? ''), $map['decimal'] ?? ',');
                $credit = self::parseAmount((string) ($cols[$map['credit_col']] ?? ''), $map['decimal'] ?? ',');
                $amount = round($credit - $debit, 2);
            } else {
                $amount = self::parseAmount((string) ($cols[$map['amount_col']] ?? ''), $map['decimal'] ?? ',');
            }
            if ($amount === 0.0) {
                continue;
            }

            $desc = isset($map['desc_col']) && $map['desc_col'] !== '' ? trim((string) ($cols[$map['desc_col']] ?? '')) : '';
            $merchant = isset($map['merchant_col']) && $map['merchant_col'] !== '' ? trim((string) ($cols[$map['merchant_col']] ?? '')) : '';
            $valueDate = isset($map['value_col']) && $map['value_col'] !== ''
                ? self::parseDate((string) ($cols[$map['value_col']] ?? ''), (string) ($map['date_format'] ?? 'Y-m-d'))
                : null;

            $movements[] = [
                'occurred_on' => $date,
                'value_date'  => $valueDate,
                'amount'      => $amount,
                'description' => $desc !== '' ? mb_substr($desc, 0, 255) : null,
                'merchant'    => $merchant !== '' ? mb_substr($merchant, 0, 191) : null,
                'reference'   => null,
            ];
        }
        return $movements;
    }

    private static function parseDate(string $raw, string $format): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $d = \DateTime::createFromFormat($format, $raw);
        if ($d !== false) {
            return $d->format('Y-m-d');
        }
        // Fallback: intent flexible.
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private static function parseAmount(string $raw, string $decimal): float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0.0;
        }
        if ($decimal === ',') {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }
        $raw = preg_replace('/[^\d.\-]/', '', $raw) ?? '';
        return round((float) $raw, 2);
    }

    /** Primeres files crues d'un CSV per a previsualització de columnes. @return array<int,array<int,string>> */
    public static function sampleCsv(string $content, string $delimiter, int $rows = 4): array
    {
        if ($delimiter === 'tab') {
            $delimiter = "\t";
        }
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = array_values(array_filter(explode("\n", $content), static fn ($l) => trim($l) !== ''));
        $out = [];
        foreach (array_slice($lines, 0, $rows) as $line) {
            $out[] = str_getcsv($line, $delimiter);
        }
        return $out;
    }
}
