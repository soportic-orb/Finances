<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AiInsight;
use App\Models\AiJob;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Recurring;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\AiService;
use App\Services\ReportService;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class AiController
{
    private const TASKS = ['categorize', 'anomalies', 'analysis', 'recommend', 'chat', 'complex'];
    private const FEATURES = ['categorize', 'analysis', 'recommend', 'anomalies', 'chat'];

    private function service(): AiService
    {
        return new AiService((int) Auth::householdId());
    }

    // ---- Configuració (owner) ----

    public function settings(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();
        $svc = $this->service();

        $models = [];
        foreach (self::TASKS as $t) {
            $models[$t] = $svc->model($t);
        }
        $features = [];
        foreach (self::FEATURES as $f) {
            $features[$f] = $svc->featureEnabled($f);
        }

        View::render('ai/settings', [
            'configured'   => $svc->isConfigured(),
            'models'       => $models,
            'features'     => $features,
            'tokenLimit'   => $svc->monthlyTokenLimit(),
            'tokensUsed'   => AiJob::tokensThisMonth($hid),
            'recent'       => AiJob::recent($hid),
            'ok'           => flash('ai_ok'),
            'error'        => flash('ai_error'),
        ], 'layouts/app');
    }

    public function saveSettings(): void
    {
        Guard::requireOwner();
        $hid = (int) Auth::householdId();

        $key = trim($_POST['api_key'] ?? '');
        if ($key !== '') {
            ApiCredential::set($hid, 'anthropic', $key);
        }
        if (isset($_POST['remove_key'])) {
            ApiCredential::delete($hid, 'anthropic');
        }

        foreach (self::TASKS as $t) {
            $m = trim($_POST['model_' . $t] ?? '');
            if ($m !== '') {
                Setting::set($hid, 'ai_model_' . $t, $m);
            }
        }
        foreach (self::FEATURES as $f) {
            Setting::set($hid, 'ai_enable_' . $f, isset($_POST['enable_' . $f]) ? '1' : '0');
        }
        Setting::set($hid, 'ai_monthly_token_limit', (string) max(0, (int) ($_POST['token_limit'] ?? 0)));

        AuditLog::record('ai_settings_updated', 'ai', null);
        flash('ai_ok', __('ai.saved'));
        redirect('/ai/settings');
    }

    // ---- Categorització ----

    public function categorize(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $rows = Transaction::rowsForCategorization($hid, true);
        View::render('ai/categorize', [
            'enabled'     => $this->service()->featureEnabled('categorize') && $this->service()->isConfigured(),
            'pending'     => count($rows),
            'suggestions' => $_SESSION['ai_suggestions'] ?? null,
            'categories'  => Category::flatLabels($hid),
            'ok'          => flash('ai_ok'),
            'error'       => flash('ai_error'),
        ], 'layouts/app');
    }

    public function suggest(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $svc = $this->service();
        if (!$svc->isConfigured() || !$svc->featureEnabled('categorize')) {
            flash('ai_error', __('ai.disabled'));
            redirect('/ai/categorize');
        }

        $rows = array_slice(Transaction::rowsForCategorization($hid, true), 0, 40);
        if ($rows === []) {
            flash('ai_ok', __('ai.nothing_to_categorize'));
            redirect('/ai/categorize');
        }

        try {
            $map = $svc->categorize($rows, Category::flatLabels($hid));
            // Desa suggeriments amb context per a la confirmació.
            $byId = [];
            foreach ($rows as $r) {
                $byId[(int) $r['id']] = $r;
            }
            $suggestions = [];
            foreach ($map as $tid => $cid) {
                if (isset($byId[$tid])) {
                    $suggestions[] = [
                        'id'          => $tid,
                        'description' => $byId[$tid]['description'] ?? ($byId[$tid]['merchant'] ?? ''),
                        'amount'      => (float) $byId[$tid]['amount'],
                        'category_id' => $cid,
                    ];
                }
            }
            $_SESSION['ai_suggestions'] = $suggestions;
            AuditLog::record('ai_categorize', 'transaction', null, count($suggestions) . ' suggeriments');
            flash('ai_ok', __('ai.suggested', ['n' => count($suggestions)]));
        } catch (\Throwable $e) {
            flash('ai_error', $e->getMessage());
        }
        redirect('/ai/categorize');
    }

    public function applyCategories(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $apply = $_POST['apply'] ?? [];
        $cats = $_POST['cat'] ?? [];
        $n = 0;
        foreach ((array) $apply as $tid) {
            $tid = (int) $tid;
            $cid = (int) ($cats[$tid] ?? 0);
            if ($tid > 0 && $cid > 0 && Category::exists($cid, $hid) && Transaction::find($tid, $hid) !== null) {
                Transaction::setCategoryAi($tid, $hid, $cid);
                $n++;
            }
        }
        unset($_SESSION['ai_suggestions']);
        AuditLog::record('ai_categorize_applied', 'transaction', null, (string) $n);
        flash('ai_ok', __('ai.applied', ['n' => $n]));
        redirect('/ai/categorize');
    }

    // ---- Anàlisi mensual ----

    public function analysis(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $latest = AiInsight::latest($hid);
        $recs = [];
        $anomalies = [];
        if ($latest && $latest['recommendations_json']) {
            $decoded = json_decode((string) $latest['recommendations_json'], true) ?: [];
            $recs = $decoded['recommendations'] ?? [];
            $anomalies = $decoded['anomalies'] ?? [];
        }
        View::render('ai/analysis', [
            'enabled'     => $this->service()->featureEnabled('analysis') && $this->service()->isConfigured(),
            'insight'     => $latest,
            'recs'        => $recs,
            'anomalies'   => $anomalies,
            'ok'          => flash('ai_ok'),
            'error'       => flash('ai_error'),
        ], 'layouts/app');
    }

    public function generateAnalysis(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $svc = $this->service();
        if (!$svc->isConfigured() || !$svc->featureEnabled('analysis')) {
            flash('ai_error', __('ai.disabled'));
            redirect('/ai/analysis');
        }
        try {
            $ctx = $this->buildContext($hid);
            $result = $svc->monthlyAnalysis($ctx);
            AiInsight::create(
                $hid,
                date('Y-m'),
                $result['summary'],
                json_encode(['recommendations' => $result['recommendations'], 'anomalies' => $result['anomalies']], JSON_UNESCAPED_UNICODE)
            );
            AuditLog::record('ai_analysis', 'ai_insight', null, date('Y-m'));
            flash('ai_ok', __('ai.analysis_done'));
        } catch (\Throwable $e) {
            flash('ai_error', $e->getMessage());
        }
        redirect('/ai/analysis');
    }

    // ---- Xat ----

    public function chat(): void
    {
        Guard::requireAuth();
        View::render('ai/chat', [
            'enabled' => $this->service()->featureEnabled('chat') && $this->service()->isConfigured(),
            'history' => $_SESSION['ai_chat'] ?? [],
            'error'   => flash('ai_error'),
        ], 'layouts/app');
    }

    public function ask(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $svc = $this->service();
        $question = trim($_POST['question'] ?? '');
        if (!$svc->isConfigured() || !$svc->featureEnabled('chat')) {
            flash('ai_error', __('ai.disabled'));
            redirect('/ai/chat');
        }
        if ($question === '') {
            redirect('/ai/chat');
        }
        try {
            $answer = $svc->chat($question, $this->buildContext($hid));
            $_SESSION['ai_chat'][] = ['q' => $question, 'a' => $answer];
            $_SESSION['ai_chat'] = array_slice($_SESSION['ai_chat'], -10);
            AuditLog::record('ai_chat', 'ai', null);
        } catch (\Throwable $e) {
            flash('ai_error', $e->getMessage());
        }
        redirect('/ai/chat');
    }

    public function clearChat(): void
    {
        Guard::requireAuth();
        unset($_SESSION['ai_chat']);
        // Si és AJAX, respon JSON; si no, redirigeix.
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            exit;
        }
        redirect('/ai/chat');
    }

    /** Xat asíncron (widget flotant). Retorna JSON amb la resposta en HTML segur. */
    public function ask_json(): void
    {
        Guard::requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $hid = (int) Auth::householdId();
        $svc = $this->service();
        $question = trim($_POST['question'] ?? '');

        if (!$svc->isConfigured() || !$svc->featureEnabled('chat')) {
            echo json_encode(['ok' => false, 'error' => __('ai.disabled')]);
            exit;
        }
        if ($question === '') {
            echo json_encode(['ok' => false, 'error' => '']);
            exit;
        }
        try {
            $answer = $svc->chat($question, $this->buildContext($hid));
            $_SESSION['ai_chat'][] = ['q' => $question, 'a' => $answer];
            $_SESSION['ai_chat'] = array_slice($_SESSION['ai_chat'], -10);
            AuditLog::record('ai_chat', 'ai', null);
            echo json_encode(['ok' => true, 'html' => \App\Support\Markdown::render($answer)], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * Construeix agregats sense dades personals (noms anonimitzats, sense IBANs).
     * @return array<string,mixed>
     */
    private function buildContext(int $hid): array
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        $summary = ReportService::monthlySummary($hid, $year, $month);

        // Anonimitza els noms de membre.
        $byMember = [];
        foreach (ReportService::byMember($hid, $summary['from'], $summary['to']) as $i => $m) {
            $byMember[] = ['member' => 'Membre ' . ($i + 1), 'expense' => $m['value']];
        }

        $subs = [];
        foreach (Recurring::allByHousehold($hid) as $r) {
            if ((int) $r['is_subscription'] === 1) {
                $subs[] = [
                    'label'   => AiService::sanitize((string) $r['label']),
                    'amount'  => (float) $r['amount_est'],
                    'cadence' => $r['cadence'],
                    'status'  => $r['status'],
                ];
            }
        }

        return [
            'period'         => sprintf('%04d-%02d', $year, $month),
            'currency'       => 'EUR',
            'income'         => $summary['income'],
            'expense'        => $summary['expense'],
            'net'            => $summary['net'],
            'savings_rate'   => $summary['savings_rate'],
            'net_worth'      => \App\Models\Account::netWorth($hid),
            'by_category'    => ReportService::categoryBreakdown($hid, $summary['from'], $summary['to']),
            'by_member'      => $byMember,
            'evolution'      => ReportService::monthlyEvolution($hid, 6),
            'subscriptions'  => $subs,
        ];
    }
}
