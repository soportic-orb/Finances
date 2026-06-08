<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\ImportTemplate;
use App\Models\Rule;
use App\Models\Transaction;
use App\Services\ImportService;
use App\Services\RuleEngine;
use App\Support\Auth;
use App\Support\Guard;
use App\Support\View;

final class ImportController
{
    public function index(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        View::render('import/index', [
            'accounts'  => Account::forSelect($hid),
            'templates' => ImportTemplate::allByHousehold($hid),
            'ok'        => flash('imp_ok'),
            'error'     => flash('imp_error'),
        ], 'layouts/app');
    }

    public function previewNorma43(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $accountId = (int) ($_POST['account_id'] ?? 0);
        if (Account::find($accountId, $hid) === null) {
            flash('imp_error', __('imp.no_account'));
            redirect('/import');
        }
        $content = $this->uploadedContent('file');
        if ($content === null) {
            flash('imp_error', __('imp.no_file'));
            redirect('/import');
        }

        $movements = ImportService::parseNorma43($content);
        $this->storePreview($accountId, 'n43', $movements);
        $this->renderPreview($hid, $accountId, $movements, 'Norma 43');
    }

    public function previewCsv(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $accountId = (int) ($_POST['account_id'] ?? 0);
        if (Account::find($accountId, $hid) === null) {
            flash('imp_error', __('imp.no_account'));
            redirect('/import');
        }
        $content = $this->uploadedContent('file');
        if ($content === null) {
            flash('imp_error', __('imp.no_file'));
            redirect('/import');
        }

        // Mapatge: des d'una plantilla o dels camps del formulari.
        $templateId = (int) ($_POST['template_id'] ?? 0);
        if ($templateId > 0 && ($tpl = ImportTemplate::find($templateId, $hid)) !== null) {
            $map = json_decode((string) $tpl['config_json'], true) ?: [];
        } else {
            $map = $this->mapFromRequest();
            // Desa com a plantilla si s'ha indicat un nom.
            $tplName = trim($_POST['save_template'] ?? '');
            if ($tplName !== '') {
                ImportTemplate::create($hid, mb_substr($tplName, 0, 128), $map);
            }
        }

        $movements = ImportService::parseCsv($content, $map);
        $this->storePreview($accountId, 'csv', $movements);
        $this->renderPreview($hid, $accountId, $movements, 'CSV');
    }

    public function confirm(): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        $data = $_SESSION['import_preview'] ?? null;
        if (!is_array($data) || empty($data['movements'])) {
            flash('imp_error', __('imp.nothing'));
            redirect('/import');
        }
        $accountId = (int) $data['account_id'];
        $account = Account::find($accountId, $hid);
        if ($account === null) {
            flash('imp_error', __('imp.no_account'));
            redirect('/import');
        }

        $rules = Rule::enabledByHousehold($hid);
        $new = 0;
        $dup = 0;
        foreach ($data['movements'] as $m) {
            $amount = (float) $m['amount'];
            $date = (string) $m['occurred_on'];
            if ($date === '' || $amount === 0.0) {
                continue;
            }
            if (Transaction::existsSimilar($accountId, $date, $amount)) {
                $dup++;
                continue;
            }
            $cat = RuleEngine::categoryFor($rules, [
                'description'  => $m['description'] ?? '',
                'merchant'     => $m['merchant'] ?? '',
                'counterparty' => '',
                'amount'       => $amount,
            ]);
            Transaction::create($hid, [
                'account_id'        => $accountId,
                'category_id'       => $cat,
                'type'              => $amount >= 0 ? 'income' : 'expense',
                'amount'            => $amount,
                'currency'          => $account['currency'],
                'occurred_on'       => $date,
                'value_date'        => $m['value_date'] ?? null,
                'description'       => $m['description'] ?? null,
                'merchant'          => $m['merchant'] ?? null,
                'counterparty'      => null,
                'notes'             => null,
                'transfer_group_id' => null,
                'source'            => 'import',
            ]);
            $new++;
        }

        // Recalcula el saldo per a comptes manuals (els EB el reben de la sincronització).
        if ($account['source'] !== 'enablebanking') {
            Account::recalc($accountId, $hid);
        }

        unset($_SESSION['import_preview']);
        AuditLog::record('import_done', 'account', $accountId, "new=$new dup=$dup src=" . ($data['source'] ?? '?'));
        flash('imp_ok', __('imp.done', ['new' => $new, 'dup' => $dup]));
        redirect('/import');
    }

    public function deleteTemplate(array $params): void
    {
        Guard::requireAuth();
        $hid = (int) Auth::householdId();
        ImportTemplate::delete((int) ($params['id'] ?? 0), $hid);
        flash('imp_ok', __('imp.tpl_deleted'));
        redirect('/import');
    }

    // ---- helpers ----

    private function uploadedContent(string $field): ?string
    {
        if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return null;
        }
        if (($_FILES[$field]['size'] ?? 0) > 8 * 1024 * 1024) {
            return null; // límit 8 MB
        }
        $raw = (string) file_get_contents($_FILES[$field]['tmp_name']);
        // Normalitza a UTF-8 (els N43/CSV espanyols solen ser ISO-8859-1).
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
        }
        return $raw;
    }

    /** @param array<int,array<string,mixed>> $movements */
    private function storePreview(int $accountId, string $source, array $movements): void
    {
        $_SESSION['import_preview'] = ['account_id' => $accountId, 'source' => $source, 'movements' => $movements];
    }

    /** @param array<int,array<string,mixed>> $movements */
    private function renderPreview(int $hid, int $accountId, array $movements, string $sourceLabel): void
    {
        $newCount = 0;
        $dupCount = 0;
        foreach ($movements as $m) {
            if (Transaction::existsSimilar($accountId, (string) $m['occurred_on'], (float) $m['amount'])) {
                $dupCount++;
            } else {
                $newCount++;
            }
        }
        View::render('import/preview', [
            'account'   => Account::find($accountId, $hid),
            'source'    => $sourceLabel,
            'movements' => array_slice($movements, 0, 50),
            'total'     => count($movements),
            'newCount'  => $newCount,
            'dupCount'  => $dupCount,
        ], 'layouts/app');
    }

    /** @return array<string,mixed> */
    private function mapFromRequest(): array
    {
        return [
            'delimiter'    => $_POST['delimiter'] ?? ',',
            'has_header'   => isset($_POST['has_header']),
            'decimal'      => $_POST['decimal'] ?? ',',
            'date_format'  => trim($_POST['date_format'] ?? 'd/m/Y'),
            'date_col'     => (int) ($_POST['date_col'] ?? 0),
            'amount_mode'  => ($_POST['amount_mode'] ?? 'single') === 'debit_credit' ? 'debit_credit' : 'single',
            'amount_col'   => (int) ($_POST['amount_col'] ?? 1),
            'debit_col'    => (int) ($_POST['debit_col'] ?? 0),
            'credit_col'   => (int) ($_POST['credit_col'] ?? 0),
            'desc_col'     => ($_POST['desc_col'] ?? '') !== '' ? (int) $_POST['desc_col'] : '',
            'merchant_col' => isset($_POST['merchant_col']) && $_POST['merchant_col'] !== '' ? (int) $_POST['merchant_col'] : '',
            'value_col'    => isset($_POST['value_col']) && $_POST['value_col'] !== '' ? (int) $_POST['value_col'] : '',
        ];
    }
}
