<?php

namespace App\Services\Render;

use App\Models\RenderedImage;
use App\Models\RenderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Job queue for the browser (Chromium) render worker on the agent box.
 *
 * One row per item. The worker claims the best pending row (priority desc, oldest first) under a lease,
 * fetches the composed SVG(s) for the hash returned by the claim, renders full.png / full.jpg / nobg.png /
 * thumb.jpg / og.jpg / anim.gif / anim.mp4, uploads them itself to {prefix}{type}/r/{id}/{hash}-b/ and
 * completes the job; the server checks every object on R2 and records the render (source=browser), which
 * then outranks the server (resvg) render until the item changes.
 */
class RenderJobs
{
    public const P_NO_IMAGE = 100;
    public const P_NEW = 90;
    public const P_CHANGED = 80;
    public const P_ANIMATED = 30;
    public const P_REST = 10;

    public const VARIANTS = ['full.png', 'full.jpg', 'thumb.jpg', 'og.jpg', 'anim.gif', 'anim.mp4'];
    public const MIMES = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'mp4' => 'video/mp4'];

    public function __construct(private ItemRenderer $renderer)
    {
    }

    /** Add / bump a job. Done or failed jobs are re-opened (the item changed or a re-render was requested). */
    public static function enqueue(string $type, int $flowId, int $priority, string $reason = ''): void
    {
        if (! config('render.jobs.enabled')) {
            return;
        }
        try {
            DB::transaction(function () use ($type, $flowId, $priority, $reason) {
                $job = RenderJob::where('item_type', $type)->where('flow_id', $flowId)->lockForUpdate()->first();
                if (! $job) {
                    RenderJob::create(['item_type' => $type, 'flow_id' => $flowId, 'priority' => $priority, 'reason' => $reason ?: null, 'status' => 'pending']);

                    return;
                }
                if ($job->status === 'leased' && $job->lease_until && $job->lease_until->isFuture()) {
                    $job->update(['requeue' => true, 'priority' => max($job->priority, $priority)]);

                    return;
                }
                $reopen = in_array($job->status, ['done', 'failed'], true);
                $job->update([
                    'status' => 'pending', 'priority' => $reopen ? $priority : max($job->priority, $priority),
                    'reason' => $reason ?: $job->reason, 'attempts' => $reopen ? 0 : $job->attempts,
                    'available_at' => $reopen ? null : $job->available_at, 'lease_until' => null, 'requeue' => false,
                ]);
            });
        } catch (Throwable $e) {
            report($e); // table missing before migrate etc.: never break the server render
        }
    }

    /** Pending jobs (incl. expired leases) in claim order. */
    public static function available()
    {
        return RenderJob::query()
            ->where(function ($q) {
                $q->where(function ($q) {
                    $q->where('status', 'pending')->where(fn ($q) => $q->whereNull('available_at')->orWhere('available_at', '<=', now()));
                })->orWhere(fn ($q) => $q->where('status', 'leased')->where('lease_until', '<', now()));
            })
            ->orderByDesc('priority')->orderBy('id');
    }

    /**
     * Claim the next job (or a specific one). Skips items that vanished or already have a browser render of
     * their current state. Returns [job, source svgs, hash] or null when there is nothing to do.
     *
     * @return array{0: RenderJob, 1: array{full: string, nobg: ?string}, 2: string}|null
     */
    public function claim(?string $type = null, ?int $flowId = null): ?array
    {
        for ($i = 0; $i < 25; $i++) {
            $job = DB::transaction(function () use ($type, $flowId) {
                $q = self::available()->lockForUpdate();
                if ($type !== null) {
                    $q->where('item_type', $type);
                }
                if ($flowId !== null) {
                    $q->where('flow_id', $flowId);
                }
                $job = $q->first();
                if ($job) {
                    $job->update(['status' => 'leased', 'lease_until' => now()->addSeconds((int) config('render.jobs.lease_seconds', 900)),
                        'attempts' => $job->attempts + 1, 'requeue' => false]);
                }

                return $job;
            });
            if (! $job) {
                return null;
            }
            $src = $this->renderer->source($job->item_type, (int) $job->flow_id);
            if (! $src) {
                $job->update(['status' => 'failed', 'last_error' => 'item or svg missing', 'lease_until' => null]);
                continue;
            }
            $hash = ItemRenderer::hash($job->item_type, $src);
            $record = RenderedImage::where('item_type', $job->item_type)->where('flow_id', $job->flow_id)->first();
            if ($record && $record->source === 'browser' && $record->hash === $hash && $record->rendered_at
                && $record->hasVariant('anim.gif') && $record->hasVariant('anim.mp4')) {
                // already complete for this state (render:import may have stored PNG/JPG only -> render again)
                $job->update(['status' => 'done', 'hash' => $hash, 'lease_until' => null, 'done_at' => now(), 'attempts' => max(0, $job->attempts - 1)]);
                continue;
            }
            $job->update(['hash' => $hash]);

            return [$job, $src, $hash];
        }

        return null;
    }

    public function isLeased(RenderJob $job): bool
    {
        // an expired lease is still accepted as long as nobody else claimed the job (status still leased)
        return $job->status === 'leased';
    }

    /** Current composed SVG for a leased job, or null if the item changed since the claim. */
    public function svg(RenderJob $job, string $variant): ?string
    {
        $src = $this->renderer->source($job->item_type, (int) $job->flow_id);
        if (! $src || ItemRenderer::hash($job->item_type, $src) !== $job->hash) {
            return null;
        }

        return $variant === 'nobg' ? $src['nobg'] : $src['full'];
    }

    /** @return string[] variants the worker must deliver for this item */
    public function expectedVariants(RenderJob $job, array $src): array
    {
        return $src['nobg'] !== null ? array_merge(self::VARIANTS, ['nobg.png']) : self::VARIANTS;
    }

    public static function keyBase(RenderJob $job, string $hash): string
    {
        return ItemRenderer::basePath($job->item_type, (int) $job->flow_id).'/'.$hash.'-b/';
    }

    /**
     * Worker finished and uploaded. Verifies every object on the render disk (size), records the browser render.
     *
     * @param  array<string, array{bytes: int}>  $files
     * @return array{status: string, error?: string}
     */
    public function complete(RenderJob $job, string $hash, array $files, ?bool $animated, ?int $renderMs): array
    {
        $src = $this->renderer->source($job->item_type, (int) $job->flow_id);
        if (! $src) {
            $job->update(['status' => 'failed', 'last_error' => 'item or svg missing', 'lease_until' => null]);

            return ['status' => 'missing'];
        }
        $current = ItemRenderer::hash($job->item_type, $src);
        if ($hash !== $job->hash || $hash !== $current) {
            // the item changed while rendering: run again for the new state
            $job->update(['status' => 'pending', 'lease_until' => null, 'requeue' => false, 'last_error' => 'stale hash '.$hash]);

            return ['status' => 'stale', 'error' => 'item changed (current hash '.$current.')'];
        }
        $need = $this->expectedVariants($job, $src);
        $missing = array_values(array_diff($need, array_keys($files)));
        if ($missing) {
            return ['status' => 'error', 'error' => 'missing variants: '.implode(',', $missing)];
        }
        $disk = Storage::disk(config('render.disk'));
        $base = self::keyBase($job, $hash);
        $bytes = 0;
        foreach ($need as $name) {
            $want = (int) ($files[$name]['bytes'] ?? -1);
            try {
                $size = $disk->size($base.$name);
            } catch (Throwable $e) {
                return ['status' => 'error', 'error' => 'not on storage: '.$name];
            }
            if ($size !== $want || $size <= 0) {
                return ['status' => 'error', 'error' => "size mismatch for {$name}: storage {$size}, worker {$want}"];
            }
            $bytes += $size;
        }
        $this->renderer->recordBrowserRender($job->item_type, (int) $job->flow_id, $hash, $need, $bytes,
            $animated ?? ItemRenderer::isAnimated($src['full']), $renderMs);
        $requeue = $job->fresh()->requeue;
        $job->update(['status' => $requeue ? 'pending' : 'done', 'requeue' => false, 'lease_until' => null, 'done_at' => now(),
            'last_error' => null, 'render_ms' => $renderMs, 'attempts' => $requeue ? 0 : $job->attempts]);

        return ['status' => 'done', 'bytes' => $bytes];
    }

    public function fail(RenderJob $job, string $error, bool $retry = true): RenderJob
    {
        $max = (int) config('render.jobs.max_attempts', 4);
        $final = ! $retry || $job->attempts >= $max;
        $job->update([
            'status' => $final ? 'failed' : 'pending', 'lease_until' => null, 'last_error' => mb_substr($error, 0, 2000),
            // 10 min, 40 min, 2.7 h ... capped at 12 h
            'available_at' => $final ? null : now()->addSeconds(min(43200, 600 * (4 ** max(0, $job->attempts - 1)))),
        ]);

        return $job;
    }

    /** @return array<string, int> */
    public static function counts(): array
    {
        return RenderJob::query()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status')->map(fn ($v) => (int) $v)->all();
    }
}
