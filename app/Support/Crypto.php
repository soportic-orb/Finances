<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Xifratge simètric en repòs amb AES-256-GCM, derivat d'APP_KEY.
 *
 * Format de sortida (base64): IV(12) || TAG(16) || CIPHERTEXT.
 * S'usa per a secrets (clau d'Anthropic, metadades d'Enable Banking).
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LEN = 12;
    private const TAG_LEN = 16;

    private static function key(): string
    {
        $raw = (string) Config::get('app.key', '');
        if (str_starts_with($raw, 'base64:')) {
            $raw = base64_decode(substr($raw, 7), true) ?: '';
        }
        if (strlen($raw) !== 32) {
            throw new RuntimeException('APP_KEY invàlida: cal una clau de 32 bytes.');
        }
        return $raw;
    }

    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $cipher = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );
        if ($cipher === false) {
            throw new RuntimeException('Ha fallat el xifratge.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < self::IV_LEN + self::TAG_LEN) {
            throw new RuntimeException('Càrrega xifrada invàlida.');
        }
        $iv = substr($decoded, 0, self::IV_LEN);
        $tag = substr($decoded, self::IV_LEN, self::TAG_LEN);
        $cipher = substr($decoded, self::IV_LEN + self::TAG_LEN);
        $plain = openssl_decrypt(
            $cipher,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($plain === false) {
            throw new RuntimeException('Ha fallat el desxifratge (clau o dades alterades).');
        }
        return $plain;
    }

    /** Genera una APP_KEY nova en format base64: per a l'instal·lador. */
    public static function generateAppKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    // ---- Xifratge per passphrase (migració de servidor) ----

    private const PBKDF2_ITER = 200000;
    private const MAGIC = 'FINBKP1';

    /** Xifra dades amb una passphrase (PBKDF2-SHA256 + AES-256-GCM). Retorna binari. */
    public static function encryptWithPassphrase(string $plaintext, string $passphrase): string
    {
        $salt = random_bytes(16);
        $key = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITER, 32, true);
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($cipher === false) {
            throw new RuntimeException('Ha fallat el xifratge del paquet.');
        }
        return self::MAGIC . $salt . $iv . $tag . $cipher;
    }

    /** Desxifra dades produïdes per encryptWithPassphrase. */
    public static function decryptWithPassphrase(string $blob, string $passphrase): string
    {
        $magicLen = strlen(self::MAGIC);
        if (strlen($blob) < $magicLen + 16 + self::IV_LEN + self::TAG_LEN || substr($blob, 0, $magicLen) !== self::MAGIC) {
            throw new RuntimeException('Paquet de migració no vàlid.');
        }
        $offset = $magicLen;
        $salt = substr($blob, $offset, 16);
        $offset += 16;
        $iv = substr($blob, $offset, self::IV_LEN);
        $offset += self::IV_LEN;
        $tag = substr($blob, $offset, self::TAG_LEN);
        $offset += self::TAG_LEN;
        $cipher = substr($blob, $offset);

        $key = hash_pbkdf2('sha256', $passphrase, $salt, self::PBKDF2_ITER, 32, true);
        $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) {
            throw new RuntimeException('Passphrase incorrecta o paquet alterat.');
        }
        return $plain;
    }
}
