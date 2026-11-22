<?php

namespace App\Services\Flow;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal client for the Flow Access REST API (https://developers.flow.com/http-api).
 * GET /v1/events accepts one type and at most 250 blocks per request.
 */
class FlowRestClient
{
    const MAX_RANGE = 250;

    protected string $base;

    public function __construct(?string $base = null)
    {
        $this->base = rtrim($base ?? (string) config('services.flow.rest'), '/');
    }

    protected function http()
    {
        return Http::baseUrl($this->base)->acceptJson()->timeout(30)->connectTimeout(10)
            ->retry(6, fn ($attempt) => min(30000, 500 * (2 ** $attempt)) + random_int(0, 250), throw: true);
    }

    public function sealedHeight(): int
    {
        $res = $this->http()->get('/v1/blocks', ['height' => 'sealed'])->throw()->json();

        return (int) $res[0]['header']['height'];
    }

    public function block(int $height): array
    {
        return $this->http()->get('/v1/blocks', ['height' => $height])->throw()->json()[0]['header'];
    }

    /**
     * Fetch events for many types over [start, end] (end - start < 250).
     * Returns flat rows: block_height, block_timestamp, type, transaction_id, transaction_index, event_index, payload.
     * Throws if any request fails after retries, so callers never advance a cursor over a gap.
     */
    public function eventsForTypes(array $types, int $start, int $end): array
    {
        if ($end - $start + 1 > self::MAX_RANGE) {
            throw new RuntimeException('Flow REST range exceeds 250 blocks');
        }

        $rows = [];
        $rps = max(1, (int) config('services.flow.indexer.rps', 20));

        foreach (array_chunk($types, $rps) as $chunk) {
            $t0 = microtime(true);
            $responses = Http::pool(function (Pool $pool) use ($chunk, $start, $end) {
                $out = [];
                foreach ($chunk as $type) {
                    $out[] = $pool->as($type)->baseUrl($this->base)->acceptJson()->timeout(30)->connectTimeout(10)
                        ->get('/v1/events', ['type' => $type, 'start_height' => $start, 'end_height' => $end]);
                }

                return $out;
            });

            foreach ($chunk as $type) {
                $res = $responses[$type] ?? null;
                if ($res instanceof \Throwable || ! $res || ! $res->successful()) {
                    // Retry this type on its own with exponential backoff (throws when exhausted).
                    $res = $this->http()->get('/v1/events', ['type' => $type, 'start_height' => $start, 'end_height' => $end]);
                }
                foreach ($res->json() ?? [] as $block) {
                    foreach ($block['events'] ?? [] as $e) {
                        $rows[] = [
                            'block_height' => (int) $block['block_height'],
                            'block_timestamp' => $block['block_timestamp'],
                            'type' => $e['type'],
                            'transaction_id' => $e['transaction_id'],
                            'transaction_index' => (int) $e['transaction_index'],
                            'event_index' => (int) $e['event_index'],
                            'payload' => $e['payload'],
                        ];
                    }
                }
            }

            // Stay under the community access node limit (default 20 requests/second).
            $elapsed = microtime(true) - $t0;
            if ($elapsed < 1.0) {
                usleep((int) ((1.0 - $elapsed) * 1e6));
            }
        }

        return $rows;
    }
}
