<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Signatura JWT RS256 amb openssl (sense dependència de llibreries externes,
 * perquè funcioni en hosting compartit sense `composer install`).
 */
final class Jwt
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param array<string,mixed> $header
     * @param array<string,mixed> $payload
     * @param string $privateKeyPem clau privada RSA en format PEM
     */
    public static function signRs256(array $header, array $payload, string $privateKeyPem): string
    {
        $key = openssl_pkey_get_private($privateKeyPem);
        if ($key === false) {
            throw new \RuntimeException('Clau privada RSA invàlida (.pem).');
        }

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('No s\'ha pogut signar el JWT.');
        }

        $segments[] = self::base64UrlEncode($signature);
        return implode('.', $segments);
    }
}
