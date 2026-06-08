<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Client HTTP mínim sobre cURL. Retorna codi, capçaleres, cos i JSON decodificat.
 */
final class Http
{
    /**
     * @param array<int,string> $headers
     * @return array{status:int,headers:array<string,string>,body:string,json:mixed}
     */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 30): array
    {
        $ch = curl_init();
        $respHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$respHeaders): int {
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($header);
            },
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            unset($ch); // curl_close() és obsolet des de PHP 8.0; el handle es allibera sol
            throw new \RuntimeException('Error de connexió HTTP: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        unset($ch);

        $bodyStr = (string) $raw;
        $json = null;
        if ($bodyStr !== '') {
            $decoded = json_decode($bodyStr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $json = $decoded;
            }
        }

        return ['status' => $status, 'headers' => $respHeaders, 'body' => $bodyStr, 'json' => $json];
    }
}
