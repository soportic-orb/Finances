<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Renderitzador de Markdown mínim i segur (sense dependències).
 *
 * Suporta: encapçalaments, negreta, cursiva, codi en línia, llistes (ordenades
 * i no), enllaços http(s), salts de línia, paràgrafs i taules estil GitHub.
 *
 * Seguretat: tot el text es passa per htmlspecialchars; les úniques etiquetes
 * de la sortida són les que genera aquest parser. Pensat per a contingut
 * generat per la IA.
 */
final class Markdown
{
    public static function render(string $md): string
    {
        $md = str_replace(["\r\n", "\r"], "\n", $md);
        $lines = explode("\n", $md);
        $n = count($lines);
        $html = '';
        $i = 0;

        while ($i < $n) {
            $line = $lines[$i];
            $t = trim($line);

            if ($t === '') {
                $i++;
                continue;
            }

            // Taula GitHub: línia amb '|' seguida d'una línia separadora.
            if (str_contains($line, '|') && isset($lines[$i + 1]) && self::isTableSep($lines[$i + 1])) {
                [$tableHtml, $i] = self::table($lines, $i);
                $html .= $tableHtml;
                continue;
            }

            // Encapçalament.
            if (preg_match('/^(#{1,6})\s+(.*)$/', $t, $m)) {
                $tag = 'h' . min(6, max(4, strlen($m[1])));
                $html .= '<' . $tag . '>' . self::inline($m[2]) . '</' . $tag . '>';
                $i++;
                continue;
            }

            // Llista no ordenada.
            if (preg_match('/^[-*]\s+/', $t)) {
                $items = [];
                while ($i < $n && preg_match('/^\s*[-*]\s+(.*)$/', $lines[$i], $mm)) {
                    $items[] = self::inline($mm[1]);
                    $i++;
                }
                $html .= '<ul>' . implode('', array_map(static fn ($it) => '<li>' . $it . '</li>', $items)) . '</ul>';
                continue;
            }

            // Llista ordenada.
            if (preg_match('/^\d+\.\s+/', $t)) {
                $items = [];
                while ($i < $n && preg_match('/^\s*\d+\.\s+(.*)$/', $lines[$i], $mm)) {
                    $items[] = self::inline($mm[1]);
                    $i++;
                }
                $html .= '<ol>' . implode('', array_map(static fn ($it) => '<li>' . $it . '</li>', $items)) . '</ol>';
                continue;
            }

            // Paràgraf: acumula fins a línia buida o inici d'un altre bloc.
            $para = [];
            while ($i < $n && trim($lines[$i]) !== '' && !self::isBlockStart($lines[$i])) {
                $para[] = self::inline($lines[$i]);
                $i++;
            }
            $html .= '<p>' . implode('<br>', $para) . '</p>';
        }

        return $html;
    }

    private static function isBlockStart(string $line): bool
    {
        $t = trim($line);
        return (bool) preg_match('/^#{1,6}\s+/', $t)
            || (bool) preg_match('/^[-*]\s+/', $t)
            || (bool) preg_match('/^\d+\.\s+/', $t);
    }

    private static function isTableSep(string $line): bool
    {
        $t = trim($line);
        return $t !== ''
            && str_contains($t, '|')
            && str_contains($t, '-')
            && (bool) preg_match('/^[\s:|-]+$/', $t);
    }

    /**
     * @param array<int,string> $lines
     * @return array{0:string,1:int}
     */
    private static function table(array $lines, int $i): array
    {
        $n = count($lines);
        $header = self::cells($lines[$i]);
        $i += 2; // salta capçalera + separadora
        $rows = [];
        while ($i < $n && trim($lines[$i]) !== '' && str_contains($lines[$i], '|')) {
            $rows[] = self::cells($lines[$i]);
            $i++;
        }

        $html = '<table class="md-table"><thead><tr>';
        foreach ($header as $c) {
            $html .= '<th>' . self::inline($c) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($header as $idx => $_) {
                $html .= '<td>' . self::inline($row[$idx] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        return [$html, $i];
    }

    /** @return array<int,string> */
    private static function cells(string $line): array
    {
        $line = trim($line);
        $line = preg_replace('/^\||\|$/', '', $line) ?? $line;
        return array_map('trim', explode('|', $line));
    }

    /** Formatat en línia sobre text escapat. */
    public static function inline(string $s): string
    {
        // Protegeix el codi en línia abans d'escapar.
        $codes = [];
        $s = preg_replace_callback('/`([^`]+)`/', static function ($m) use (&$codes) {
            $codes[] = $m[1];
            return "\x01" . (count($codes) - 1) . "\x01";
        }, $s) ?? $s;

        $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $s) ?? $s;
        $s = preg_replace('/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/s', '<em>$1</em>', $s) ?? $s;

        $s = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', static function ($m) {
            $href = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
        }, $s) ?? $s;

        // Restaura el codi (amb contingut escapat).
        $s = preg_replace_callback('/\x01(\d+)\x01/', static function ($m) use ($codes) {
            return '<code>' . htmlspecialchars($codes[(int) $m[1]] ?? '', ENT_QUOTES, 'UTF-8') . '</code>';
        }, $s) ?? $s;

        return $s;
    }
}
