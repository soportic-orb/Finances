<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Support\Auth;
use App\Support\Guard;

/**
 * Exportació de moviments a CSV i Excel (HTML .xls). Respecta filtres per query.
 */
final class ExportController
{
    /** @return array<string,mixed> */
    private function filters(): array
    {
        $validDate = static function (string $d): ?string {
            $dt = \DateTime::createFromFormat('Y-m-d', trim($d));
            return ($dt && $dt->format('Y-m-d') === trim($d)) ? trim($d) : null;
        };
        return [
            'account'  => (int) ($_GET['account'] ?? 0) ?: null,
            'category' => (int) ($_GET['category'] ?? 0) ?: null,
            'type'     => $_GET['type'] ?? null,
            'q'        => trim($_GET['q'] ?? ''),
            'from'     => $validDate($_GET['from'] ?? ''),
            'to'       => $validDate($_GET['to'] ?? ''),
        ];
    }

    public function csv(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $rows = Transaction::allForExport($hid, $this->filters());
        AuditLog::record('export_csv', 'transaction', null, count($rows) . ' files');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="moviments-' . date('Ymd') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 per a Excel
        fputcsv($out, ['Data', 'Data valor', 'Compte', 'Tipus', 'Categoria', 'Descripció', 'Comerç', 'Import', 'Moneda']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['occurred_on'], $r['value_date'] ?? '', $r['account_name'], $r['type'],
                $r['category_name'] ?? '', $r['description'] ?? '', $r['merchant'] ?? '',
                number_format((float) $r['amount'], 2, ',', ''), $r['account_currency'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function xls(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $rows = Transaction::allForExport($hid, $this->filters());
        AuditLog::record('export_xls', 'transaction', null, count($rows) . ' files');

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="moviments-' . date('Ymd') . '.xls"');

        echo "\xEF\xBB\xBF<table border=\"1\"><tr>"
            . '<th>Data</th><th>Data valor</th><th>Compte</th><th>Tipus</th><th>Categoria</th>'
            . '<th>Descripció</th><th>Comerç</th><th>Import</th><th>Moneda</th></tr>';
        foreach ($rows as $r) {
            echo '<tr>'
                . '<td>' . e($r['occurred_on']) . '</td>'
                . '<td>' . e($r['value_date'] ?? '') . '</td>'
                . '<td>' . e($r['account_name']) . '</td>'
                . '<td>' . e($r['type']) . '</td>'
                . '<td>' . e($r['category_name'] ?? '') . '</td>'
                . '<td>' . e($r['description'] ?? '') . '</td>'
                . '<td>' . e($r['merchant'] ?? '') . '</td>'
                . '<td>' . number_format((float) $r['amount'], 2, ',', '.') . '</td>'
                . '<td>' . e($r['account_currency']) . '</td>'
                . '</tr>';
        }
        echo '</table>';
        exit;
    }
}
