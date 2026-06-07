<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Renderitzat de vistes PHP amb layout. Tota sortida s'escapa amb e() a la vista.
 */
final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        echo self::capture($template, $data, $layout);
    }

    /** @param array<string,mixed> $data */
    public static function capture(string $template, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::renderFile($template, $data);
        if ($layout !== null) {
            $content = self::renderFile($layout, array_merge($data, ['content' => $content]));
        }
        return $content;
    }

    /** @param array<string,mixed> $data */
    private static function renderFile(string $template, array $data): string
    {
        $file = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Vista no trobada: $template");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
