<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingFlowEvents;
use App\Services\Flow\EventIngestor;
use App\Services\Flow\FlowEventTypes;
use App\Services\Flow\FlowRestClient;
use App\Services\Flow\IndexerCursor;
use Illuminate\Console\Command;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

/**
 * Live tail of Sportvatar events over the Flow Access WebSocket stream (`events` topic).
 * Runs as a Forge daemon (Supervisor). Resumes from syncs['indexer:stream'] + 1; events are
 * stored with insertOrIgnore, so overlap with flow:catchup is harmless.
 * Docs: https://developers.flow.com/protocol/access-onchain-data/websockets-stream-api
 */
class FlowStream extends Command
{
    protected $signature = 'flow:stream {--from= : start height (overrides the cursor)} {--heartbeat=10 : heartbeat interval in blocks} {--max-runtime=3600 : exit after N seconds (Supervisor restarts it)}';

    protected $description = 'Stream Sportvatar events from the Flow WebSocket API (daemon)';

    protected bool $stop = false;

    public function handle(EventIngestor $ingestor, FlowRestClient $rest): int
    {
        if (! config('services.flow.indexer.enabled')) {
            $this->warn('Indexer disabled (FLOW_INDEXER_ENABLED=false); sleeping');
            sleep(60);

            return self::SUCCESS;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $this->stop = true);
            pcntl_signal(SIGINT, fn () => $this->stop = true);
        }

        $deadline = time() + (int) $this->option('max-runtime');
        $cursor = $this->option('from') !== null ? (int) $this->option('from') - 1 : (IndexerCursor::get(IndexerCursor::STREAM) ?? IndexerCursor::initial());
        // The public stream only serves recent history; far behind -> let flow:catchup fill the gap.
        $sealed = $rest->sealedHeight();
        if ($sealed - $cursor > 50000) {
            $this->warn("Stream cursor $cursor is far behind sealed $sealed; starting at sealed-1000 (flow:catchup fills the gap)");
            $cursor = $sealed - 1000;
        }

        $types = FlowEventTypes::all();
        $filter = [
            'addresses' => ['0x'.FlowEventTypes::contractAddress()],
            'event_types' => FlowEventTypes::storefrontTypes(),
        ];
        $backoff = 1;

        while (! $this->stop && time() < $deadline) {
            try {
                $client = new Client((string) config('services.flow.ws'));
                $client->setTimeout(90)->setFrameSize(1024 * 1024)
                    ->addMiddleware(new CloseHandler())->addMiddleware(new PingResponder());
                $subId = 'sportvatar-'.substr(md5((string) microtime(true)), 0, 8);
                $client->text(json_encode([
                    'subscription_id' => $subId,
                    'action' => 'subscribe',
                    'topic' => 'events',
                    'arguments' => $filter + [
                        'start_block_height' => (string) ($cursor + 1),
                        'heartbeat_interval' => (string) $this->option('heartbeat'),
                    ],
                ]));
                $this->info("Subscribed from height ".($cursor + 1));
                $backoff = 1;
                $buffer = [];
                $lastFlush = microtime(true);

                while (! $this->stop && time() < $deadline) {
                    $message = $client->receive();
                    if (! $message || ! method_exists($message, 'getContent')) {
                        continue;
                    }
                    $data = json_decode($message->getContent(), true);
                    if (! is_array($data)) {
                        continue;
                    }
                    if (! empty($data['error'])) {
                        throw new \RuntimeException('Flow WS error: '.json_encode($data['error']));
                    }
                    $payload = $data['payload'] ?? null;
                    if (! $payload || ($data['subscription_id'] ?? null) !== $subId || ! isset($payload['block_height'])) {
                        continue;
                    }
                    $height = (int) $payload['block_height'];
                    foreach ($payload['events'] ?? [] as $e) {
                        if (! in_array($e['type'], $types, true)) {
                            continue;
                        }
                        $buffer[] = [
                            'block_height' => $height,
                            'block_timestamp' => $payload['block_timestamp'],
                            'type' => $e['type'],
                            'transaction_id' => $e['transaction_id'],
                            'transaction_index' => (int) $e['transaction_index'],
                            'event_index' => (int) $e['event_index'],
                            'payload' => $e['payload'],
                        ];
                    }
                    $cursor = max($cursor, $height);

                    if ($buffer || microtime(true) - $lastFlush > 5) {
                        $inserted = 0;
                        if ($buffer) {
                            [$inserted] = $ingestor->store($buffer, 'stream');
                            $buffer = [];
                        }
                        IndexerCursor::set(IndexerCursor::STREAM, $cursor);
                        $lastFlush = microtime(true);
                        if ($inserted > 0) {
                            $this->line("block $cursor: +$inserted events");
                            ProcessPendingFlowEvents::dispatch();
                        }
                    }
                }
                $client->close();
            } catch (\Throwable $e) {
                $this->error('Stream error: '.substr($e->getMessage(), 0, 300).'; reconnecting in '.$backoff.'s');
                report($e);
                sleep($backoff);
                $backoff = min(60, $backoff * 2);
            }
        }

        IndexerCursor::set(IndexerCursor::STREAM, $cursor);
        $this->info("Stopped at height $cursor");

        return self::SUCCESS;
    }
}
