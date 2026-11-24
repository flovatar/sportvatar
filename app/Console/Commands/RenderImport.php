<?php

namespace App\Console\Commands;

use App\Services\Render\ItemRenderer;
use Illuminate\Console\Command;

/**
 * Register a browser (Chromium) render for an item; it then outranks the server (resvg) render until
 * the item changes. {dir} holds the variant files (full.png, full.jpg, nobg.png, thumb.jpg, og.jpg, …).
 *   php8.5 artisan render:import flovatar 1763 /home/forge/render-inbox/flovatar-1763 --hash=abcdef123456
 */
class RenderImport extends Command
{
    protected $signature = 'render:import {type} {flowId} {dir} {--hash= : refuse if the item changed since this content hash (from render:animated --with-hash)}';

    protected $description = 'Register browser-rendered images for an item (preferred over the server render)';

    public function handle(ItemRenderer $renderer): int
    {
        $dir = rtrim((string) $this->argument('dir'), '/');
        $files = [];
        foreach (glob($dir.'/*.{png,jpg,gif,mp4,webp}', GLOB_BRACE) ?: [] as $path) {
            $files[basename($path)] = $path;
        }
        if (! isset($files['full.png'])) {
            $this->error('need at least full.png in '.$dir);

            return self::FAILURE;
        }
        $res = $renderer->importBrowserRender((string) $this->argument('type'), (int) $this->argument('flowId'), $files, $this->option('hash') ?: null);
        $this->line(json_encode($res));

        return $res['status'] === 'imported' ? self::SUCCESS : self::FAILURE;
    }
}
