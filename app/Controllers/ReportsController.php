<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Services\ReportService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class ReportsController
{
    /** Informe mensual com a pàgina HTML imprimible. */
    public function monthly(): void
    {
        Guard::requireAuth();
        $data = $this->buildData();
        // Vista integrada a la interfície de l'app.
        View::render('reports/monthly', $data, 'layouts/app');
    }

    /** Descàrrega de l'informe en PDF (dompdf si està instal·lat). */
    public function pdf(): void
    {
        Guard::requireAuth();
        $data = $this->buildData();
        $html = View::capture('reports/document', $data + ['embedded' => false], null);
        AuditLog::record('export_pdf', 'report', null, $data['periodLabel']);

        $filename = 'informe-' . $data['year'] . '-' . sprintf('%02d', $data['month']) . '.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream($filename, ['Attachment' => true]);
            exit;
        }

        // Sense dompdf: serveix l'HTML imprimible (Imprimeix → Desa com a PDF).
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /** @return array<string,mixed> */
    private function buildData(): array
    {
        $hid = (int) Auth::householdId();
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        $month = max(1, min(12, $month));

        $summary = ReportService::monthlySummary($hid, $year, $month);
        $months = ['', 'Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny', 'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre'];

        return [
            'household'      => Household::find($hid),
            'year'           => $year,
            'month'          => $month,
            'periodLabel'    => $months[$month] . ' ' . $year,
            'summary'        => $summary,
            'breakdown'      => ReportService::categoryBreakdown($hid, $summary['from'], $summary['to']),
            'byMember'       => ReportService::byMember($hid, $summary['from'], $summary['to']),
            'evolution'      => ReportService::monthlyEvolution($hid, 12),
            'netWorthSeries' => ReportService::netWorthSeries($hid, 12),
        ];
    }
}
