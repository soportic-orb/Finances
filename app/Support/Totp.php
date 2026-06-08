<?php

declare(strict_types=1);

namespace App\Support;

/**
 * TOTP (RFC 6238) per al 2FA opcional. Sense dependències externes.
 * Compatible amb Google Authenticator, Authy, etc. (SHA1, 6 dígits, 30 s).
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;

    /** Genera un secret base32 aleatori. */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /** Codi TOTP per a un instant concret (útil per a proves i verificació). */
    public static function codeAt(string $secret, int $timestamp, int $digits = 6): string
    {
        $counter = (int) floor($timestamp / self::PERIOD);
        $key = self::base32Decode($secret);
        $bin = pack('J', $counter); // 64-bit big-endian
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $part = (ord($hash[$offset]) & 0x7F) << 24
            | (ord($hash[$offset + 1]) & 0xFF) << 16
            | (ord($hash[$offset + 2]) & 0xFF) << 8
            | (ord($hash[$offset + 3]) & 0xFF);
        $otp = $part % (10 ** $digits);
        return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
    }

    /** Verifica un codi amb una finestra de tolerància (per defecte ±1 període). */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            $expected = self::codeAt($secret, $now + ($i * self::PERIOD));
            if (hash_equals($expected, $code)) {
                return true;
            }
        }
        return false;
    }

    /** URI otpauth:// per a apps autenticadores (entrada manual o QR). */
    public static function provisioningUri(string $secret, string $label, string $issuer): string
    {
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => 6,
            'period'    => self::PERIOD,
        ]);
        return sprintf(
            'otpauth://totp/%s:%s?%s',
            rawurlencode($issuer),
            rawurlencode($label),
            $params
        );
    }

    private static function base32Encode(string $data): string
    {
        $out = '';
        $buffer = 0;
        $bits = 0;
        foreach (str_split($data) as $char) {
            $buffer = ($buffer << 8) | ord($char);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $out .= self::ALPHABET[($buffer >> $bits) & 0x1F];
            }
        }
        if ($bits > 0) {
            $out .= self::ALPHABET[($buffer << (5 - $bits)) & 0x1F];
        }
        return $out;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper($b32);
        $buffer = 0;
        $bits = 0;
        $out = '';
        $len = strlen($b32);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos(self::ALPHABET, $b32[$i]);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        return $out;
    }
}
