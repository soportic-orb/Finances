<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rule;
use App\Models\Transaction;

/**
 * Motor de categorització per regles deterministes.
 *
 * Aplica la primera regla activa (per prioritat) que coincideix amb un camp del
 * moviment. S'usa en ingesta (manual i Enable Banking) i sota demanda.
 */
final class RuleEngine
{
    /**
     * Avalua una regla contra els camps d'un moviment.
     * @param array<string,mixed> $rule
     * @param array<string,mixed> $fields description/merchant/counterparty/amount
     */
    public static function matches(array $rule, array $fields): bool
    {
        $field = (string) $rule['field'];
        $pattern = (string) $rule['pattern'];

        if ($field === 'amount') {
            $value = isset($fields['amount']) ? (string) abs((float) $fields['amount']) : '';
        } else {
            $value = (string) ($fields[$field] ?? '');
        }
        if ($value === '' && $pattern !== '') {
            return false;
        }

        return match ($rule['match_type']) {
            'conte'  => mb_stripos($value, $pattern) !== false,
            'exacte' => $field === 'amount'
                ? abs((float) $value - (float) $pattern) < 0.001
                : mb_strtolower($value) === mb_strtolower($pattern),
            'regex'  => self::safeRegex($pattern, $value),
            default  => false,
        };
    }

    private static function safeRegex(string $pattern, string $value): bool
    {
        $delimited = '~' . str_replace('~', '\~', $pattern) . '~iu';
        set_error_handler(static fn () => true);
        $result = preg_match($delimited, $value);
        restore_error_handler();
        return $result === 1;
    }

    /**
     * Primera categoria que assignaria el conjunt de regles, o null.
     * @param array<int,array<string,mixed>> $rules ja ordenades per prioritat
     * @param array<string,mixed> $fields
     */
    public static function categoryFor(array $rules, array $fields): ?int
    {
        foreach ($rules as $rule) {
            if (self::matches($rule, $fields)) {
                return (int) $rule['set_category_id'];
            }
        }
        return null;
    }

    /**
     * Aplica les regles als moviments existents de la llar.
     *
     * @param string $mode 'uncategorized' (només sense categoria) | 'all' (recategoritza tot)
     * @return int nombre de moviments actualitzats
     */
    public static function applyToHousehold(int $householdId, string $mode = 'uncategorized'): int
    {
        $rules = Rule::enabledByHousehold($householdId);
        if ($rules === []) {
            return 0;
        }

        $rows = Transaction::rowsForCategorization($householdId, $mode === 'uncategorized');
        $updated = 0;
        foreach ($rows as $row) {
            $catId = self::categoryFor($rules, $row);
            if ($catId !== null && (int) ($row['category_id'] ?? 0) !== $catId) {
                Transaction::setCategory((int) $row['id'], $householdId, $catId);
                $updated++;
            }
        }
        return $updated;
    }
}
