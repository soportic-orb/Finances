<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sistema d'internacionalització (i18n). Català per defecte + castellà.
 * Cap text a pèl al codi: tot passa per __('clau').
 */
final class Lang
{
    private static string $locale = 'ca';
    /** @var array<string,array<string,string>> */
    private static array $messages = [];

    public static function setLocale(string $locale): void
    {
        self::$locale = in_array($locale, ['ca', 'es'], true) ? $locale : 'ca';
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    public static function get(string $key, array $replace = []): string
    {
        if (!isset(self::$messages[self::$locale])) {
            $file = BASE_PATH . '/app/Support/lang/' . self::$locale . '.php';
            self::$messages[self::$locale] = is_file($file) ? (require $file) : [];
        }
        $text = self::$messages[self::$locale][$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }
}
