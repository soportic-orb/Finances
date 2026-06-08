<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Execució de comandes externes (git, composer) amb captura de sortida i codi.
 */
final class Shell
{
    /**
     * @param array<int,string> $args
     * @return array{code:int,output:string}
     */
    public static function run(array $args, ?string $cwd = null): array
    {
        $cmd = implode(' ', array_map('escapeshellarg', $args));
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = @proc_open($cmd, $descriptors, $pipes, $cwd);
        if (!is_resource($proc)) {
            return ['code' => 127, 'output' => 'No s\'ha pogut executar: ' . $args[0]];
        }
        $out = stream_get_contents($pipes[1]) ?: '';
        $err = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        return ['code' => $code, 'output' => trim($out . "\n" . $err)];
    }

    public static function available(string $binary): bool
    {
        $res = self::run(['sh', '-c', 'command -v ' . escapeshellarg($binary)]);
        return $res['code'] === 0 && $res['output'] !== '';
    }
}
