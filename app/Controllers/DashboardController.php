<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Household;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        Guard::requireAuth();
        $householdId = (int) Auth::householdId();

        View::render('dashboard/index', [
            'household'   => Household::find($householdId),
            'memberCount' => Household::memberCount($householdId),
            'recent'      => AuditLog::recent($householdId, 10),
        ], 'layouts/app');
    }
}
