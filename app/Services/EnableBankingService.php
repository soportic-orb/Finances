<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Support\Http;
use App\Support\Jwt;

/**
 * Client d'Enable Banking (PSD2): autenticació JWT RS256, flux d'autorització,
 * sessions i lectura de saldos/transaccions.
 *
 * Mai registra secrets ni dades sensibles als logs.
 */
final class EnableBankingService
{
    public const DEFAULT_BASE_URL = 'https://api.enablebanking.com';
    private const JWT_TTL = 3600;          // 1 h
    private const JWT_CACHE_TTL = 3300;    // 55 min
    private const MAX_429_RETRIES = 3;

    public function __construct(private int $householdId)
    {
    }

    public function isConfigured(): bool
    {
        return $this->applicationId() !== '' && is_file($this->keyPath());
    }

    public function applicationId(): string
    {
        return (string) Setting::get($this->householdId, 'eb_application_id', '');
    }

    public function environment(): string
    {
        return (string) Setting::get($this->householdId, 'eb_environment', 'production');
    }

    public function baseUrl(): string
    {
        return rtrim((string) Setting::get($this->householdId, 'eb_base_url', self::DEFAULT_BASE_URL), '/');
    }

    public function redirectUrl(): string
    {
        return (string) Setting::get($this->householdId, 'eb_redirect_url', '');
    }

    public function keyPath(): string
    {
        $appId = $this->applicationId();
        return BASE_PATH . '/config/keys/' . preg_replace('/[^A-Za-z0-9\-]/', '', $appId) . '.pem';
    }

    /** Genera (o reusa) el JWT signat amb la clau privada. */
    public function jwt(): string
    {
        $cacheFile = BASE_PATH . '/storage/cache/eb_jwt_' . $this->householdId . '.json';
        if (is_file($cacheFile)) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached) && ($cached['exp'] ?? 0) > time()) {
                return (string) $cached['jwt'];
            }
        }

        $appId = $this->applicationId();
        if ($appId === '' || !is_file($this->keyPath())) {
            throw new \RuntimeException('Enable Banking no està configurat (application_id o .pem).');
        }
        $pem = (string) file_get_contents($this->keyPath());

        $now = time();
        $jwt = Jwt::signRs256(
            ['typ' => 'JWT', 'alg' => 'RS256', 'kid' => $appId],
            ['iss' => 'enablebanking.com', 'aud' => 'api.enablebanking.com', 'iat' => $now, 'exp' => $now + self::JWT_TTL],
            $pem
        );

        @file_put_contents($cacheFile, json_encode(['jwt' => $jwt, 'exp' => $now + self::JWT_CACHE_TTL]));
        @chmod($cacheFile, 0600);
        return $jwt;
    }

    public function clearJwtCache(): void
    {
        @unlink(BASE_PATH . '/storage/cache/eb_jwt_' . $this->householdId . '.json');
    }

    /**
     * Petició autenticada amb gestió de 422 (PSU headers) i 429 (backoff).
     * @param array<string,scalar> $query
     * @param array<string,mixed>|null $body
     * @return array{status:int,json:mixed,body:string,headers:array<string,string>}
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null, bool $withPsu = false, int $attempt = 0): array
    {
        $url = $this->baseUrl() . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: Bearer ' . $this->jwt(),
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        if ($withPsu) {
            $ip = (string) Setting::get($this->householdId, 'eb_psu_ip', '');
            $ua = (string) Setting::get($this->householdId, 'eb_psu_ua', 'Finances/1.0');
            if ($ip !== '') {
                $headers[] = 'PSU-IP-Address: ' . $ip;
            }
            $headers[] = 'PSU-User-Agent: ' . $ua;
        }

        $resp = Http::request($method, $url, $headers, $body !== null ? json_encode($body, JSON_UNESCAPED_SLASHES) : null);

        // 422 amb PSU header no proveït → reintenta una vegada amb PSU headers.
        if ($resp['status'] === 422 && !$withPsu && str_contains($resp['body'], 'PSU_HEADER_NOT_PROVIDED')) {
            return $this->request($method, $path, $query, $body, true, $attempt);
        }

        // 429 → backoff exponencial.
        if ($resp['status'] === 429 && $attempt < self::MAX_429_RETRIES) {
            $wait = (int) ($resp['headers']['retry-after'] ?? (2 ** $attempt));
            sleep(max(1, min(30, $wait)));
            return $this->request($method, $path, $query, $body, $withPsu, $attempt + 1);
        }

        return $resp;
    }

    /** Llista d'ASPSPs (bancs) per país. */
    public function getAspsps(string $country = 'ES'): array
    {
        return $this->request('GET', '/aspsps', ['country' => $country]);
    }

    /** Inicia l'autorització; retorna url de consentiment + authorization_id. */
    public function startAuthorization(string $aspspName, string $country, string $validUntilIso, string $state, string $psuType = 'personal'): array
    {
        return $this->request('POST', '/auth', [], [
            'access'       => ['valid_until' => $validUntilIso],
            'aspsp'        => ['name' => $aspspName, 'country' => $country],
            'psu_type'     => $psuType,
            'state'        => $state,
            'redirect_url' => $this->redirectUrl(),
        ], true);
    }

    /** Crea la sessió a partir del code del callback. */
    public function createSession(string $code): array
    {
        return $this->request('POST', '/sessions', [], ['code' => $code], true);
    }

    public function getAccountBalances(string $accountUid): array
    {
        return $this->request('GET', '/accounts/' . rawurlencode($accountUid) . '/balances', [], null, true);
    }

    /**
     * Transaccions d'un compte amb paginació per continuation_key.
     * @return array{status:int,json:mixed,body:string,headers:array<string,string>}
     */
    public function getAccountTransactions(string $accountUid, ?string $dateFrom = null, ?string $dateTo = null, ?string $continuationKey = null): array
    {
        $query = [];
        if ($dateFrom !== null) {
            $query['date_from'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $query['date_to'] = $dateTo;
        }
        if ($continuationKey !== null) {
            $query['continuation_key'] = $continuationKey;
        }
        return $this->request('GET', '/accounts/' . rawurlencode($accountUid) . '/transactions', $query, null, true);
    }
}
