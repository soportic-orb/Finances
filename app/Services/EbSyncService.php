<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\EbAccountLink;
use App\Models\EbSyncLog;
use App\Models\Rule;
use App\Models\Transaction;

/**
 * Sincronització de saldos i transaccions d'Enable Banking cap al model local,
 * amb deduplicació per external_ref i paginació per continuation_key.
 */
final class EbSyncService
{
    private const MAX_PAGES = 50;

    public function __construct(private EnableBankingService $eb)
    {
    }

    /**
     * Converteix una transacció d'Enable Banking al format intern.
     * amount queda amb signe (DBIT negatiu, CRDT positiu).
     *
     * @param array<string,mixed> $t
     * @return array<string,mixed>|null  null si no es pot determinar la referència
     */
    public static function mapTransaction(array $t, int $accountId, int $householdId, string $fallbackCurrency): ?array
    {
        $ref = $t['entry_reference'] ?? ($t['transaction_id'] ?? null);
        if ($ref === null || $ref === '') {
            return null; // sense referència no podem deduplicar de forma fiable
        }

        $amountRaw = $t['transaction_amount']['amount'] ?? ($t['amount'] ?? '0');
        $amount = abs((float) $amountRaw);
        $indicator = strtoupper((string) ($t['credit_debit_indicator'] ?? 'DBIT'));
        $signed = $indicator === 'CRDT' ? $amount : -$amount;

        $currency = $t['transaction_amount']['currency'] ?? $fallbackCurrency;

        $remittance = $t['remittance_information'] ?? [];
        $description = is_array($remittance) ? trim(implode(' ', $remittance)) : trim((string) $remittance);
        if ($description === '') {
            $description = (string) ($t['bank_transaction_code']['description'] ?? '');
        }

        // Contrapart segons direcció.
        $counterparty = $indicator === 'CRDT'
            ? ($t['debtor']['name'] ?? null)
            : ($t['creditor']['name'] ?? null);

        $bookingDate = $t['booking_date'] ?? ($t['value_date'] ?? date('Y-m-d'));
        $valueDate = $t['value_date'] ?? null;

        return [
            'account_id'        => $accountId,
            'category_id'       => null,
            'type'              => $signed >= 0 ? 'income' : 'expense',
            'amount'            => round($signed, 2),
            'currency'          => $currency,
            'occurred_on'       => substr((string) $bookingDate, 0, 10),
            'value_date'        => $valueDate ? substr((string) $valueDate, 0, 10) : null,
            'description'       => $description !== '' ? mb_substr($description, 0, 255) : null,
            'merchant'          => $counterparty ? mb_substr((string) $counterparty, 0, 191) : null,
            'counterparty'      => $counterparty ? mb_substr((string) $counterparty, 0, 191) : null,
            'notes'             => null,
            'transfer_group_id' => null,
            'source'            => 'enablebanking',
            'external_ref'      => mb_substr((string) $ref, 0, 191),
        ];
    }

    /**
     * Extreu el saldo preferit (closing booked) d'una resposta de balances.
     * @param array<string,mixed> $balancesJson
     */
    public static function preferredBalance(array $balancesJson, string $fallbackCurrency): ?float
    {
        $balances = $balancesJson['balances'] ?? [];
        if (!is_array($balances) || $balances === []) {
            return null;
        }
        // Preferència: CLBD (closing booked), després el primer disponible.
        foreach ($balances as $b) {
            if (strtoupper((string) ($b['balance_type'] ?? '')) === 'CLBD') {
                return (float) ($b['balance_amount']['amount'] ?? 0);
            }
        }
        return (float) ($balances[0]['balance_amount']['amount'] ?? 0);
    }

    /**
     * Sincronitza un enllaç de compte. Retorna [new, dup].
     * @param array<string,mixed> $link fila de eb_account_links (+ account info)
     * @return array{0:int,1:int}
     */
    public function syncLink(array $link): array
    {
        $linkId = (int) $link['id'];
        $accountId = (int) $link['account_id'];
        $householdId = (int) $link['household_id'];
        $uid = (string) $link['eb_account_uid'];
        $currency = (string) ($link['currency'] ?? 'EUR');

        $logId = EbSyncLog::start($linkId);
        $new = 0;
        $dup = 0;

        try {
            $dateFrom = $link['last_synced_at']
                ? date('Y-m-d', strtotime((string) $link['last_synced_at']))
                : date('Y-m-d', strtotime('-90 days'));
            $dateTo = date('Y-m-d');

            // Regles actives per categoritzar en ingesta.
            $rules = Rule::enabledByHousehold($householdId);

            $continuation = null;
            $pages = 0;
            do {
                $resp = $this->eb->getAccountTransactions($uid, $dateFrom, $dateTo, $continuation);
                if ($resp['status'] !== 200 || !is_array($resp['json'])) {
                    throw new \RuntimeException('Resposta inesperada (' . $resp['status'] . ') en transaccions.');
                }
                $txs = $resp['json']['transactions'] ?? [];
                foreach ($txs as $t) {
                    $mapped = self::mapTransaction($t, $accountId, $householdId, $currency);
                    if ($mapped === null) {
                        continue;
                    }
                    if (Transaction::existsExternal($accountId, $mapped['external_ref'])) {
                        $dup++;
                        continue;
                    }
                    if ($rules !== []) {
                        $mapped['category_id'] = RuleEngine::categoryFor($rules, $mapped);
                    }
                    Transaction::create($householdId, $mapped);
                    $new++;
                }
                $continuation = $resp['json']['continuation_key'] ?? null;
                $pages++;
            } while ($continuation !== null && $pages < self::MAX_PAGES);

            // Saldo: prioritza el reportat per EB; si no, recalcula.
            $balResp = $this->eb->getAccountBalances($uid);
            if ($balResp['status'] === 200 && is_array($balResp['json'])) {
                $bal = self::preferredBalance($balResp['json'], $currency);
                if ($bal !== null) {
                    Account::setCurrentBalance($accountId, $bal);
                } else {
                    Account::recalc($accountId, $householdId);
                }
            } else {
                Account::recalc($accountId, $householdId);
            }

            EbAccountLink::markSynced($linkId, null);
            EbSyncLog::finish($logId, $new, $dup, 'ok');
        } catch (\Throwable $e) {
            EbSyncLog::finish($logId, $new, $dup, 'error', $e->getMessage());
            throw $e;
        }

        return [$new, $dup];
    }
}
