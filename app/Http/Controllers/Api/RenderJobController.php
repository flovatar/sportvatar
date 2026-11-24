<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RenderJob;
use App\Services\Render\ItemRenderer;
use App\Services\Render\RenderJobs;
use Illuminate\Http\Request;

/**
 * Job API for the browser render worker (agent box). Auth: bearer RENDER_WORKER_TOKEN (middleware
 * AuthenticateRenderWorker), stateless 'api' group (no session / CSRF / cookies), rate-limited.
 *
 *   GET  /api/render-jobs?limit=N               pending jobs in claim order + counts
 *   POST /api/render-jobs/claim  {type?, id?}   lease the next job -> hash, storage key base, svg urls
 *   GET  /api/render-jobs/{job}/svg/{full|nobg} composed SVG (site layer order) of the claimed item state
 *   POST /api/render-jobs/{job}/complete        {hash, files: {variant: {bytes}}, animated, render_ms}
 *   POST /api/render-jobs/{job}/fail            {error, retry}
 */
class RenderJobController extends Controller
{
    public function __construct(private RenderJobs $jobs)
    {
    }

    public function index(Request $request)
    {
        $limit = max(1, min(100, (int) $request->query('limit', 20)));

        return response()->json([
            'counts' => RenderJobs::counts(),
            'jobs' => RenderJobs::available()->limit($limit)->get(['id', 'item_type', 'flow_id', 'priority', 'attempts', 'reason'])
                ->map(fn ($j) => ['id' => $j->id, 'type' => $j->item_type, 'flow_id' => (int) $j->flow_id, 'priority' => $j->priority,
                    'attempts' => $j->attempts, 'reason' => $j->reason]),
        ]);
    }

    public function claim(Request $request)
    {
        if (config('render.disk') !== 'r2') {
            return response()->json(['error' => 'render disk is not r2'], 503);
        }
        $data = $request->validate([
            'type' => 'nullable|string|in:'.implode(',', ItemRenderer::TYPES),
            'id' => 'nullable|integer|min:0',
        ]);
        $claimed = $this->jobs->claim($data['type'] ?? null, isset($data['id']) ? (int) $data['id'] : null);
        if (! $claimed) {
            return response()->json(['job' => null]);
        }
        [$job, $src, $hash] = $claimed;

        return response()->json(['job' => [
            'id' => $job->id,
            'type' => $job->item_type,
            'flow_id' => (int) $job->flow_id,
            'hash' => $hash,
            'priority' => $job->priority,
            'attempt' => $job->attempts,
            'lease_until' => $job->lease_until->toIso8601String(),
            'animated_hint' => ItemRenderer::isAnimated($src['full']),
            'storage' => [
                'bucket' => (string) config('render.bucket'),
                'prefix' => (string) config('render.prefix'),
                'key_base' => RenderJobs::keyBase($job, $hash),
                'public_url' => (string) config('render.public_url'),
                'cache_control' => (string) config('render.cache_control'),
            ],
            'variants' => $this->jobs->expectedVariants($job, $src),
            'svg' => [
                'full' => '/api/render-jobs/'.$job->id.'/svg/full',
                'nobg' => $src['nobg'] !== null ? '/api/render-jobs/'.$job->id.'/svg/nobg' : null,
            ],
            'options' => ['size' => (int) config('render.size', 1000), 'thumb' => (int) config('render.thumb', 400),
                'og' => config('render.og', [1200, 630]), 'jpeg_quality' => (int) config('render.jpeg_quality', 88)],
        ]]);
    }

    public function svg(RenderJob $job, string $variant)
    {
        if (! $this->jobs->isLeased($job)) {
            return response()->json(['error' => 'job not leased'], 409);
        }
        $svg = $this->jobs->svg($job, $variant);
        if ($svg === null) {
            return response()->json(['error' => 'stale: item changed since the claim, or no such variant'], 409);
        }

        return response($svg, 200, ['Content-Type' => 'image/svg+xml; charset=utf-8', 'X-Render-Hash' => $job->hash]);
    }

    public function complete(Request $request, RenderJob $job)
    {
        if (! $this->jobs->isLeased($job)) {
            return response()->json(['status' => 'error', 'error' => 'job not leased (status '.$job->status.')'], 409);
        }
        $data = $request->validate([
            'hash' => 'required|string|regex:/^[0-9a-f]{12}$/',
            'files' => 'required|array|max:12',
            'files.*.bytes' => 'required|integer|min:1',
            'animated' => 'nullable|boolean',
            'render_ms' => 'nullable|integer|min:0',
        ]);
        foreach (array_keys($data['files']) as $name) {
            if (! preg_match('/^(full\.png|full\.jpg|nobg\.png|thumb\.jpg|og\.jpg|anim\.gif|anim\.mp4)$/', (string) $name)) {
                return response()->json(['status' => 'error', 'error' => 'bad variant '.$name], 422);
            }
        }
        $res = $this->jobs->complete($job, $data['hash'], $data['files'], $data['animated'] ?? null, $data['render_ms'] ?? null);
        $code = match ($res['status']) { 'done' => 200, 'stale' => 409, 'missing' => 410, default => 422 };
        if ($res['status'] === 'done') {
            info('render worker: '.$job->item_type.':'.$job->flow_id.' browser render '.$data['hash'].' recorded');
        }

        return response()->json($res, $code);
    }

    public function fail(Request $request, RenderJob $job)
    {
        $data = $request->validate(['error' => 'required|string|max:4000', 'retry' => 'nullable|boolean']);
        if (! $this->jobs->isLeased($job)) {
            return response()->json(['status' => 'ignored'], 409);
        }
        $job = $this->jobs->fail($job, $data['error'], $data['retry'] ?? true);

        return response()->json(['status' => $job->status, 'available_at' => $job->available_at?->toIso8601String()]);
    }
}
