<?php

namespace App\Console\Commands;

use App\Jobs\SyncSportvatar;
use App\Services\FlowRest;
use App\Services\Flow\FlowEventTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Rebuilds NFT ownership from the current chain state, for history the indexer cannot replay.
 *
 * Sportvatar's event history has a hole: Graffle stopped at the Crescendo spork (2024-09-04) and
 * the historic access nodes for mainnet25 and mainnet26 (2024-09-04 -> 2025-10-22) no longer
 * resolve. This command reads the Sportvatar, Sportbit and SportvatarPack collections of every
 * address the database knows (stored owners, users, every address seen in events) and the
 * contract account, then:
 *   - sets flow_address for every NFT found in one of those collections;
 *   - clears flow_address of NFTs that are no longer held by their stored owner and were not found
 *     anywhere else (equipped Sportbits, burned Flames, opened packs, or a wallet the database has
 *     never seen); Sportbits recorded at the contract account are kept (they sit inside unopened packs);
 *   - with --listings, removes sale offers whose storefront listing no longer exists, was
 *     purchased, or is ghosted (the seller no longer holds the NFT);
 *   - with --resync, re-reads every owned Sportvatar from the chain (metadata, accessories, ability).
 * A JSON report with every change is written to storage/app/reconcile/.
 */
class ReconcileChain extends Command
{
    protected $signature = 'sportvatar:reconcile-chain
        {--dry-run : report only, change nothing}
        {--listings : also verify open sale offers against NFTStorefrontV2}
        {--resync : re-read every owned Sportvatar from the chain afterwards}
        {--batch=20 : addresses per script call}
        {--sleep=250 : milliseconds between script calls (public ExecuteScript limit is ~5/s)}';

    protected $description = 'Reconcile Sportvatar/Sportbit/pack owners (and optionally listings) with the current chain state';

    const MODELS = [
        0 => ['sportvatars', 'App\Models\Sportvatar'],
        1 => ['sportbits', 'App\Models\Sportbit'],
        2 => ['packs', 'App\Models\Pack'],
    ];

    public function handle(): int
    {
        $c = '0x'.FlowEventTypes::contractAddress();
        $script = <<<CDC
import Sportvatar from $c
import Sportbit from $c
import SportvatarPack from $c

access(all) fun main(addresses: [Address]): {Address: [[UInt64]]} {
    let out: {Address: [[UInt64]]} = {}
    for a in addresses {
        let acct = getAccount(a)
        var sv: [UInt64] = []
        var sb: [UInt64] = []
        if let col = acct.capabilities.borrow<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath) { sv = col.getIDs() }
        if let col = acct.capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath) { sb = col.getIDs() }
        out[a] = [sv, sb, SportvatarPack.getPacks(address: a) ?? []]
    }
    return out
}
CDC;

        $addresses = $this->candidateAddresses();
        $this->info('Checking '.count($addresses).' addresses on chain');

        $owner = [[], [], []];
        $checked = [];
        $calls = 0;
        foreach (array_chunk($addresses, max(1, (int) $this->option('batch'))) as $chunk) {
            // The contract account holds tens of thousands of Sportbits/packs: query it alone.
            $groups = in_array($c, $chunk, true) ? array_merge([[$c]], [array_values(array_diff($chunk, [$c]))]) : [$chunk];
            foreach ($groups as $group) {
                if (! $group) {
                    continue;
                }
                $arg = ['type' => 'Array', 'value' => array_map(fn ($a) => ['type' => 'Address', 'value' => $a], $group)];
                $result = null;
                for ($try = 0; $try < 4 && $result === null; $try++) {
                    try {
                        $result = FlowRest::script($script, [$arg]);
                    } catch (\Throwable $e) {
                        $this->warn('  script failed ('.substr($e->getMessage(), 0, 160).'), retrying');
                        sleep(2 + $try * 3);
                    }
                }
                if ($result === null) {
                    $this->error('  giving up on '.implode(',', $group).' (their NFTs are left unchanged)');

                    continue;
                }
                foreach ($result as $address => $lists) {
                    $address = strtolower($address);
                    $checked[$address] = true;
                    foreach ($lists as $i => $ids) {
                        foreach ($ids as $id) {
                            $owner[$i][(int) $id] = $address;
                        }
                    }
                }
                $calls++;
                usleep((int) $this->option('sleep') * 1000);
            }
            if ($calls % 10 === 0) {
                $this->line("  $calls calls, ".count($checked).' addresses read');
            }
        }

        $report = ['generated_at' => now()->toIso8601String(), 'addresses_checked' => count($checked), 'models' => []];
        foreach (self::MODELS as $i => [$table, $model]) {
            $stats = ['found' => count($owner[$i]), 'moved' => 0, 'cleared' => 0, 'unchanged' => 0, 'in_pack' => 0, 'kept_unverified' => 0, 'not_in_db' => 0];
            $changes = [];
            $rows = DB::table($table)->whereNotNull('flow_id')->get($table === 'sportbits' ? ['id', 'flow_id', 'flow_address', 'packed'] : ['id', 'flow_id', 'flow_address']);
            $known = [];
            foreach ($rows as $row) {
                $known[(int) $row->flow_id] = true;
                $stored = $row->flow_address ? strtolower($row->flow_address) : null;
                $actual = $owner[$i][(int) $row->flow_id] ?? null;
                if ($actual !== null) {
                    if ($stored !== $actual) {
                        $stats['moved']++;
                        $changes[] = [$row->flow_id, $stored, $actual];
                        if (! $this->option('dry-run')) {
                            DB::table($table)->where('id', $row->id)->update(['flow_address' => $actual, 'updated_at' => now()]);
                        }
                    } else {
                        $stats['unchanged']++;
                    }
                } elseif ($table === 'sportbits' && $stored === $c) {
                    // Not in the contract's Sportbit collection: inside an unopened pack there (the `packed`
                    // flag is not reliable). Opening the pack deposits it to the buyer (Sportbit.Deposit).
                    $stats['in_pack']++;
                } elseif ($stored !== null && isset($checked[$stored])) {
                    // The stored owner was read and does not hold it, and nobody we know does.
                    $stats['cleared']++;
                    $changes[] = [$row->flow_id, $stored, null];
                    if (! $this->option('dry-run')) {
                        DB::table($table)->where('id', $row->id)->update(['flow_address' => null, 'updated_at' => now()]);
                    }
                } elseif ($stored !== null) {
                    $stats['kept_unverified']++;
                }
            }
            $stats['not_in_db'] = count(array_diff_key($owner[$i], $known));
            $this->info(sprintf('%s: %s', $table, json_encode($stats)));
            $report['models'][$table] = ['stats' => $stats, 'changes' => $changes,
                'not_in_db' => array_slice(array_keys(array_diff_key($owner[$i], $known)), 0, 1000)];
        }

        if ($this->option('listings')) {
            $report['listings'] = $this->reconcileListings($owner);
        }

        $path = 'reconcile/reconcile-'.now()->format('Ymd-His').($this->option('dry-run') ? '-dry' : '').'.json';
        Storage::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Report: storage/app/'.$path);

        if ($this->option('resync') && ! $this->option('dry-run')) {
            $list = DB::table('sportvatars')->whereNotNull('flow_address')->orderBy('flow_id')->get(['flow_id', 'flow_address']);
            $this->info('Re-reading '.$list->count().' Sportvatars from chain');
            $bar = $this->output->createProgressBar($list->count());
            foreach ($list as $row) {
                try {
                    SyncSportvatar::dispatchSync($row->flow_id, $row->flow_address);
                } catch (\Throwable $e) {
                    $this->warn("  #{$row->flow_id}: ".substr($e->getMessage(), 0, 120));
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        return self::SUCCESS;
    }

    /** Every address the database has ever associated with Sportvatar NFTs, plus the contract account. */
    protected function candidateAddresses(): array
    {
        $set = ['0x'.FlowEventTypes::contractAddress() => true];
        foreach (['sportvatars', 'sportbits', 'packs', 'sale_offers'] as $table) {
            foreach (DB::table($table)->whereNotNull('flow_address')->distinct()->pluck('flow_address') as $a) {
                $set[strtolower($a)] = true;
            }
        }
        foreach (DB::table('users')->pluck('email') as $a) {
            $set[strtolower($a)] = true;
        }
        foreach (['flow_seller_address', 'flow_buyer_address'] as $col) {
            foreach (DB::table('sales')->whereNotNull($col)->distinct()->pluck($col) as $a) {
                $set[strtolower($a)] = true;
            }
        }
        foreach (['to', 'from', 'address', 'storefrontAddress'] as $key) {
            $vals = DB::table('events')->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(flow_metadata, '$.\"$key\"')) a")
                ->whereRaw("JSON_EXTRACT(flow_metadata, '$.\"$key\"') IS NOT NULL")->pluck('a');
            foreach ($vals as $a) {
                $set[strtolower($a)] = true;
            }
        }

        return array_values(array_filter(array_keys($set), fn ($a) => (bool) preg_match('/^0x[0-9a-f]{16}$/', $a)));
    }

    /**
     * Remove sale offers whose listing is gone or purchased, or whose seller no longer holds the NFT
     * (a "ghost" listing that can't be bought).
     */
    protected function reconcileListings(array $owner): array
    {
        $sf = '0x'.FlowEventTypes::storefrontAddress();
        $script = <<<CDC
import NFTStorefrontV2 from $sf

access(all) fun main(seller: Address, listingIds: [UInt64]): {UInt64: Bool} {
    let out: {UInt64: Bool} = {}
    let storefront = getAccount(seller).capabilities.borrow<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontPublicPath)
    for id in listingIds {
        var ok = false
        if let s = storefront {
            if let l = s.borrowListing(listingResourceID: id) {
                ok = !l.getDetails().purchased
            }
        }
        out[id] = ok
    }
    return out
}
CDC;
        $offers = DB::table('sale_offers')->where('is_completed', false)->get(['id', 'flow_address', 'listing_id', 'item_type', 'flow_id']);
        $stats = ['open' => $offers->count(), 'valid' => 0, 'removed' => 0, 'ghost' => 0, 'no_listing_id' => 0, 'errors' => 0];
        $modelIndex = ['App\\Models\\Sportvatar' => 0, 'App\\Models\\Sportbit' => 1, 'App\\Models\\Pack' => 2];
        $drop = function ($o, $seller, $why) use (&$stats, &$removed) {
            $stats[$why]++;
            $removed[] = [$o->item_type, $o->flow_id, $seller, $o->listing_id, $why];
            if (! $this->option('dry-run')) {
                DB::table('sale_offers')->where('id', $o->id)->delete();
            }
        };
        $removed = [];
        foreach ($offers->groupBy('flow_address') as $seller => $list) {
            $withId = $list->filter(fn ($o) => $o->listing_id);
            foreach ($list->reject(fn ($o) => $o->listing_id) as $o) {
                $drop($o, $seller, 'no_listing_id');
            }
            foreach ($withId as $o) {
                $holder = $owner[$modelIndex[$o->item_type] ?? 1][(int) $o->flow_id] ?? null;
                if ($holder !== strtolower($seller)) {
                    $drop($o, $seller, 'ghost');

                    continue;
                }
                // One listing per call: legacy listings can make the storefront script panic.
                $arg = ['type' => 'Array', 'value' => [['type' => 'UInt64', 'value' => (string) $o->listing_id]]];
                try {
                    $res = FlowRest::script($script, [['type' => 'Address', 'value' => $seller], $arg]);
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $res = [];
                }
                if (! empty($res[(string) $o->listing_id])) {
                    $stats['valid']++;
                } else {
                    $drop($o, $seller, 'removed');
                }
                usleep((int) $this->option('sleep') * 1000);
            }
        }
        $this->info('listings: '.json_encode($stats));

        return ['stats' => $stats, 'removed' => $removed];
    }
}
