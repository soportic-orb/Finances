<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiJob;
use App\Models\ApiCredential;
use App\Models\Setting;
use App\Support\Http;

/**
 * Capa d'IA centralitzada sobre l'API de Claude (Anthropic).
 *
 * - Clau d'API xifrada en repòs (via ApiCredential).
 * - Model per tasca configurable.
 * - Minimització de dades: mai IBANs/targetes/noms reals; opt-in per funció.
 * - Registre a ai_jobs amb payload_summary; límit de despesa mensual.
 *
 * El transport és injectable per a proves (sense xarxa).
 */
final class AiService
{
    public const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    public const VERSION = '2023-06-01';

    /** Models per defecte per tasca (configurables a settings). */
    private const DEFAULT_MODELS = [
        'categorize' => 'claude-haiku-4-5-20251001',
        'anomalies'  => 'claude-haiku-4-5-20251001',
        'analysis'   => 'claude-sonnet-4-6',
        'recommend'  => 'claude-sonnet-4-6',
        'chat'       => 'claude-sonnet-4-6',
        'complex'    => 'claude-opus-4-8',
    ];

    /** @var (callable(string,array):array)|null */
    private $transport;

    /** @param (callable(string,array):array)|null $transport */
    public function __construct(private int $householdId, ?callable $transport = null)
    {
        $this->transport = $transport;
    }

    public function isConfigured(): bool
    {
        return ApiCredential::exists($this->householdId, 'anthropic');
    }

    public function featureEnabled(string $feature): bool
    {
        return Setting::get($this->householdId, 'ai_enable_' . $feature, '0') === '1';
    }

    public function model(string $task): string
    {
        $default = self::DEFAULT_MODELS[$task] ?? self::DEFAULT_MODELS['chat'];
        return (string) Setting::get($this->householdId, 'ai_model_' . $task, $default);
    }

    public function monthlyTokenLimit(): int
    {
        return (int) Setting::get($this->householdId, 'ai_monthly_token_limit', '0');
    }

    /**
     * Minimització: elimina IBANs, números de targeta i seqüències llargues de dígits.
     */
    public static function sanitize(string $text): string
    {
        $text = preg_replace('/\b[A-Z]{2}\d{2}[A-Z0-9]{8,30}\b/', '[IBAN]', $text) ?? $text;
        $text = preg_replace('/\b(?:\d[ -]?){13,19}\b/', '[NUM]', $text) ?? $text;
        return $text;
    }

    /**
     * Crida l'API i registra a ai_jobs. Llança excepció en error.
     * @return array<string,mixed> JSON de resposta
     */
    private function call(string $type, string $model, string $system, string $userContent, int $maxTokens, string $summary): array
    {
        $limit = $this->monthlyTokenLimit();
        if ($limit > 0 && AiJob::tokensThisMonth($this->householdId) >= $limit) {
            throw new \RuntimeException('S\'ha assolit el límit de tokens d\'IA d\'aquest mes.');
        }

        $payload = [
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $userContent]],
        ];

        $resp = $this->transport !== null
            ? ($this->transport)($model, $payload)
            : $this->httpCall($payload);

        $json = $resp['json'] ?? null;
        $status = ($resp['status'] >= 200 && $resp['status'] < 300 && is_array($json)) ? 'ok' : 'error';
        $in = (int) ($json['usage']['input_tokens'] ?? 0);
        $out = (int) ($json['usage']['output_tokens'] ?? 0);

        AiJob::log($this->householdId, $type, $model, $in, $out, $status, $summary);

        if ($status !== 'ok') {
            throw new \RuntimeException('Error de l\'API d\'IA (' . ($resp['status'] ?? 0) . ').');
        }
        return $json;
    }

    /** @param array<string,mixed> $payload */
    private function httpCall(array $payload): array
    {
        $key = ApiCredential::get($this->householdId, 'anthropic');
        if ($key === null || $key === '') {
            throw new \RuntimeException('Falta la clau d\'API d\'Anthropic.');
        }
        $headers = [
            'x-api-key: ' . $key,
            'anthropic-version: ' . self::VERSION,
            'content-type: application/json',
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Reintents en 429/5xx amb backoff.
        for ($attempt = 0; ; $attempt++) {
            $resp = Http::request('POST', self::ENDPOINT, $headers, $body, 60);
            if (in_array($resp['status'], [429, 500, 529], true) && $attempt < 3) {
                sleep(2 ** $attempt);
                continue;
            }
            return $resp;
        }
    }

    /** Extreu el text concatenat de la resposta. @param array<string,mixed> $json */
    public static function textOf(array $json): string
    {
        $out = '';
        foreach (($json['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                $out .= $block['text'];
            }
        }
        return $out;
    }

    /** Parseja JSON estricte tolerant a tanques de codi Markdown. @return mixed */
    public static function parseJson(string $text): mixed
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t) ?? $t;
        $t = preg_replace('/\s*```$/', '', $t) ?? $t;
        // Si hi ha text addicional, intenta extreure el primer objecte/array.
        if (!str_starts_with($t, '{') && !str_starts_with($t, '[')) {
            if (preg_match('/(\{.*\}|\[.*\])/s', $t, $m)) {
                $t = $m[1];
            }
        }
        return json_decode($t, true);
    }

    // ---- Funcions d'IA ----

    /**
     * Categorització: retorna [transaction_id => category_id].
     * @param array<int,array<string,mixed>> $transactions [{id,merchant,description,amount}]
     * @param array<int,string> $categories id => label
     * @return array<int,int>
     */
    public function categorize(array $transactions, array $categories): array
    {
        $txPayload = array_map(fn ($t) => [
            'id'          => (int) $t['id'],
            'merchant'    => self::sanitize((string) ($t['merchant'] ?? '')),
            'description' => self::sanitize((string) ($t['description'] ?? '')),
            'amount'      => (float) $t['amount'],
        ], $transactions);
        $catPayload = [];
        foreach ($categories as $id => $label) {
            $catPayload[] = ['id' => (int) $id, 'label' => $label];
        }

        $system = 'Ets un classificador de despeses domèstiques. Assigna a cada transacció la '
            . 'categoria més adient de la llista. Respon NOMÉS amb JSON vàlid, sense Markdown ni text: '
            . '{"assignments":[{"id":<int>,"category_id":<int>}]}. Usa només category_id de la llista; '
            . 'omet les que no sàpigues classificar.';
        $user = json_encode(['transactions' => $txPayload, 'categories' => $catPayload], JSON_UNESCAPED_UNICODE);

        $json = $this->call('categorize', $this->model('categorize'), $system, (string) $user, 1500,
            'categorize: ' . count($transactions) . ' tx (merchant,description,amount sanititzats)');

        $parsed = self::parseJson(self::textOf($json));
        $out = [];
        foreach (($parsed['assignments'] ?? []) as $a) {
            $tid = (int) ($a['id'] ?? 0);
            $cid = (int) ($a['category_id'] ?? 0);
            if ($tid > 0 && isset($categories[$cid])) {
                $out[$tid] = $cid;
            }
        }
        return $out;
    }

    /**
     * Anàlisi mensual a partir d'agregats. Retorna summary + recommendations + anomalies.
     * @param array<string,mixed> $aggregates
     * @return array{summary:string,recommendations:array<int,string>,anomalies:array<int,string>}
     */
    public function monthlyAnalysis(array $aggregates): array
    {
        $system = 'Ets un analista financer de la llar. Analitza els agregats (sense dades personals) '
            . 'i respon NOMÉS amb JSON vàlid en català, sense Markdown: '
            . '{"summary":"<2-4 frases>","recommendations":["<3-5 accions>"],"anomalies":["<0-3 fets inusuals>"]}.';
        $user = json_encode($aggregates, JSON_UNESCAPED_UNICODE);

        $json = $this->call('analysis', $this->model('analysis'), $system, (string) $user, 1200,
            'analysis: agregats mensuals (categories, totals, evolució)');

        $parsed = self::parseJson(self::textOf($json));
        return [
            'summary'         => (string) ($parsed['summary'] ?? ''),
            'recommendations' => array_values(array_map('strval', $parsed['recommendations'] ?? [])),
            'anomalies'       => array_values(array_map('strval', $parsed['anomalies'] ?? [])),
        ];
    }

    /**
     * Xat: redacta una resposta basada NOMÉS en els agregats proporcionats.
     * @param array<string,mixed> $context
     */
    public function chat(string $question, array $context): string
    {
        $system = 'Ets l\'assistent financer d\'una llar. Respon en català, breu i concret, basant-te '
            . 'NOMÉS en les dades agregades proporcionades (no contenen noms ni IBANs). No inventis xifres; '
            . 'si no tens la dada, digues-ho. Les recomanacions són orientatives.';
        $user = 'Dades agregades:' . "\n" . json_encode($context, JSON_UNESCAPED_UNICODE)
            . "\n\nPregunta: " . self::sanitize($question);

        $json = $this->call('chat', $this->model('chat'), $system, $user, 800, 'chat: pregunta + agregats');
        return trim(self::textOf($json));
    }
}
