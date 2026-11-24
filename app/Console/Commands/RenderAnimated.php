<?php

namespace App\Console\Commands;

use App\Services\Render\AnimatedItems;
use App\Services\Render\ItemRenderer;
use Illuminate\Console\Command;

/**
 * List animated items (CSS @keyframes / SMIL) - candidates for the browser renderer.
 *   php8.5 artisan render:animated sportvatar --json [--with-hash]
 */
class RenderAnimated extends Command
{
    protected $signature = 'render:animated {type=all : '.'sportvatar|template|all} {--json} {--with-hash : include the current content hash per item (needed by render:import --hash)}';

    protected $description = 'List items whose SVG is animated (browser-render candidates)';

    public function handle(ItemRenderer $renderer): int
    {
        $types = $this->argument('type') === 'all' ? ItemRenderer::TYPES : [$this->argument('type')];
        $out = [];
        foreach ($types as $type) {
            $ids = AnimatedItems::ids($type);
            if ($this->option('with-hash')) {
                $map = [];
                foreach ($ids as $id) {
                    $src = $renderer->source($type, $id);
                    $map[$id] = $src ? ItemRenderer::hash($type, $src) : null;
                }
                $out[$type] = $map;
            } else {
                $out[$type] = $ids;
            }
            if (! $this->option('json')) {
                $this->info($type.': '.count($ids).' animated');
            }
        }
        if ($this->option('json')) {
            $this->line(json_encode($out, JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
