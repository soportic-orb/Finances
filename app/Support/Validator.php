<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validador mínim per a entrades de formulari.
 * Regles suportades: required, email, min:N, max:N, numeric, in:a,b,c.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $rules camp => "required|email|min:3"
     */
    public function __construct(private array $data, private array $rules)
    {
    }

    public function passes(): bool
    {
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleset) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->apply($field, $value, $name, $param);
            }
        }
        return $this->errors === [];
    }

    private function apply(string $field, mixed $value, string $rule, ?string $param): void
    {
        if (isset($this->errors[$field])) {
            return; // un sol error per camp
        }
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->errors[$field] = "El camp $field és obligatori.";
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = "El camp $field no és un correu vàlid.";
                }
                break;
            case 'min':
                if ($value !== null && mb_strlen((string) $value) < (int) $param) {
                    $this->errors[$field] = "El camp $field ha de tenir com a mínim $param caràcters.";
                }
                break;
            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    $this->errors[$field] = "El camp $field no pot superar $param caràcters.";
                }
                break;
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    $this->errors[$field] = "El camp $field ha de ser numèric.";
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    $this->errors[$field] = "El valor de $field no és vàlid.";
                }
                break;
        }
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
