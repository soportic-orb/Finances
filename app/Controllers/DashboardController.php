<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\Household;
use App\Services\ReportService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();

        $year = (int) date('Y');
        $month = (int) date('n');
        $summary = ReportService::monthlySummary($hid, $year, $month);

        View::render('dashboard/index', [
            'household'    => Household::find($hid),
            'netWorth'     => Account::netWorth($hid),
            'summary'      => $summary,
            'breakdown'    => ReportService::categoryBreakdown($hid, $summary['from'], $summary['to']),
            'byMember'     => ReportService::byMember($hid, $summary['from'], $summary['to']),
            'evolution'    => ReportService::monthlyEvolution($hid, 12),
            'netWorthSeries' => ReportService::netWorthSeries($hid, 12),
        ], 'layouts/app');
    }
}
