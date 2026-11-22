<?php

namespace App\Console\Commands;

use App\Services\Flow\FlowEventTypes;
use App\Support\Blocklist;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Flags NFTs linked to the 2026-11-16 incident: everything deposited into a blocklisted address
 * (0x0c7deb7a0c9cb424 claimed 1,039 Genesis Free Packs with leaked v1 codes, opened them and
 * received 2,078 Sportbits) plus the claimed packs themselves. Optionally imports an inventory
 * file (flovatar-kb/audit/incident-2026-11-16/{packs,sportbits}.json) so items whose events
 * predate the indexer are covered too. Flagged items are hidden from the marketplace and their
 * pages show a notice.
 */
class FlagIncidentItems extends Command
{
    protected $signature = 'sportvatar:flag-incident-items
        {--since=2026-11-16 00:00:00 : only deposits at/after this block time}
        {--inventory=* : JSON inventory files with an "items" list of {id} (packs.json / sportbits.json)}';

    protected $description = 'Flag NFTs received by blocklisted addresses (incident 2026-11-16)';

    const MODELS = [
        'Sportbit.Deposit' => 'App\Models\Sportbit',
        'Sportvatar.Deposit' => 'App\Models\Sportvatar',
        'SportvatarPack.Deposit' => 'App\Models\Pack',
    ];

    public function handle(): int
    {
        $addresses = Blocklist::addresses();
        if (! $addresses) {
            $this->warn('No blocklisted addresses configured');

            return self::SUCCESS;
        }
        $now = now();
        $total = 0;
        foreach (self::MODELS as $event => $model) {
            $rows = DB::table('events')->where('type', FlowEventTypes::type($event))->where('event_date', '>=', $this->option('since'))
                ->whereIn(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(flow_metadata, '$.to'))"), $addresses)
                ->get(['transaction_id', 'flow_metadata']);
            $insert = [];
            foreach ($rows as $r) {
                $m = json_decode($r->flow_metadata, true);
                $insert[] = ['item_type' => $model, 'flow_id' => (int) $m['id'], 'reason' => 'incident-2026-11-16',
                    'address' => $m['to'], 'transaction_id' => $r->transaction_id, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($insert, 500) as $chunk) {
                $total += DB::table('flagged_items')->insertOrIgnore($chunk);
            }
            $this->line("$model: ".count($insert).' deposits from events');
        }

        foreach ((array) $this->option('inventory') as $file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (! is_array($data)) {
                $this->error("Cannot read $file");

                continue;
            }
            $model = str_contains(basename($file), 'pack') ? 'App\Models\Pack' : 'App\Models\Sportbit';
            $items = $data['items'] ?? $data['packs'] ?? $data['sportbits'] ?? [];
            $address = $data['incident_address'] ?? ($addresses[0] ?? null);
            $insert = [];
            foreach ($items as $item) {
                $id = is_array($item) ? ($item['id'] ?? $item['pack_id'] ?? $item['sportbit_id'] ?? null) : $item;
                if ($id === null) {
                    continue;
                }
                $insert[] = ['item_type' => $model, 'flow_id' => (int) $id, 'reason' => 'incident-2026-11-16',
                    'address' => $address, 'transaction_id' => is_array($item) ? ($item['claimed_tx'] ?? $item['received_tx'] ?? null) : null,
                    'created_at' => $now, 'updated_at' => $now];
            }
            $added = 0;
            foreach (array_chunk($insert, 500) as $chunk) {
                $added += DB::table('flagged_items')->insertOrIgnore($chunk);
            }
            $total += $added;
            $this->line(basename($file).": $model ".count($insert)." listed, $added new");
        }

        Cache::flush();
        $this->info("Flagged $total new items (total ".DB::table('flagged_items')->count().')');

        return self::SUCCESS;
    }
}
