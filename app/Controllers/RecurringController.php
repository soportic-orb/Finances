<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AuditLog;
use App\Models\Recurring;
use App\Services\RecurringDetector;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class RecurringController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        View::render('recurring/index', [
            'items' => Recurring::allByHousehold($hid),
            'ok'    => flash('rec_ok'),
            'error' => flash('rec_error'),
        ], 'layouts/app');
    }

    public function detect(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $patterns = RecurringDetector::detect($hid);

        Recurring::clearDetected($hid);
        foreach ($patterns as $p) {
            Recurring::insert($hid, $p);
        }
        AuditLog::record('recurring_detected', 'recurring', null, count($patterns) . ' patrons');
        flash('rec_ok', __('rec.detected', ['n' => count($patterns)]));
        redirect('/recurring');
    }

    /** @param array<string,string> $params */
    public function delete(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        Recurring::delete((int) ($params['id'] ?? 0), $hid);
        flash('rec_ok', __('rec.deleted'));
        redirect('/recurring');
    }
}
