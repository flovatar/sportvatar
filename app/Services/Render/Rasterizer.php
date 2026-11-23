<?php

namespace App\Services\Render;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * SVG -> PNG with the resvg CLI (static binary, ~15 MB RSS, ~0.1 s for a 1000 px Flovatar).
 * Runs under nice + an address-space cap so a bad SVG can't starve PHP-FPM on the shared box.
 * Feed it SVGs that went through SvgFlattener: resvg ignores sibling selectors and animations.
 */
class Rasterizer
{
    public function png(string $svg, int $width, int $height): string
    {
        $bin = (string) config('render.resvg');
        if (! is_file($bin) || ! is_executable($bin)) {
            throw new RuntimeException('resvg binary not found or not executable: '.$bin);
        }

        $resources = storage_path('framework/cache/render-resources'); // empty dir: no local file access from <image href>
        if (! is_dir($resources)) {
            @mkdir($resources, 0775, true);
        }

        $cmd = [$bin, '-', '-c', '-w', (string) $width, '-h', (string) $height, '--resources-dir', $resources];
        if ($fonts = config('render.fonts_dir')) {
            array_push($cmd, '--skip-system-fonts', '--use-fonts-dir', (string) $fonts);
        }
        if (($nice = (int) config('render.nice')) > 0 && is_executable('/usr/bin/nice')) {
            $cmd = array_merge(['/usr/bin/nice', '-n', (string) min(19, $nice)], $cmd);
        }
        if (($mb = (int) config('render.memory_mb')) > 0 && is_executable('/usr/bin/prlimit')) {
            $cmd = array_merge(['/usr/bin/prlimit', '--as='.($mb * 1048576)], $cmd);
        }

        $process = new Process($cmd, null, ['RUST_LOG' => 'error'], $svg, (float) config('render.timeout', 60));
        $process->run();
        $out = $process->getOutput();
        if (! $process->isSuccessful() || ! str_starts_with($out, "\x89PNG")) {
            throw new RuntimeException('resvg failed (exit '.$process->getExitCode().'): '.mb_substr(trim($process->getErrorOutput()), 0, 500));
        }

        return $out;
    }
}
