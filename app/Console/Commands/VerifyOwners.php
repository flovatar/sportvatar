<?php

namespace App\Console\Commands;

use App\Services\Flow\FlowEventTypes;
use Flow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Spot-checks NFT owners stored in the database against mainnet: for each sampled NFT,
 * runs a holds_* Cadence script (collection getIDs().contains) against the stored owner. Samples NFTs touched by indexer events (most likely
 * to be wrong) plus random ones.
 */
class VerifyOwners extends Command
{
    protected $signature = 'sportvatar:verify-owners {--sample=30 : NFTs per model} {--recent-only : only NFTs touched by indexer events} {--ids= : comma separated flow ids (with --model)} {--model=sportvatars : sportvatars|sportbits}';

    protected $description = 'Compare stored NFT owners with mainnet for a sample of Sportvatars and Sportbits';

    public function handle(): int
    {
        $models = [
            'sportvatars' => ['./scripts/holds_sportvatar.cdc', FlowEventTypes::type('Sportvatar.Deposit')],
            'sportbits' => ['./scripts/holds_sportbit.cdc', FlowEventTypes::type('Sportbit.Deposit')],
        ];
        $n = (int) $this->option('sample');
        $totals = ['ok' => 0, 'mismatch' => 0, 'no_owner' => 0, 'in_pack' => 0];
        $contract = '0x'.FlowEventTypes::contractAddress();

        foreach ($models as $table => [$script, $depositType]) {
            if ($this->option('ids')) {
                if ($table !== $this->option('model')) {
                    continue;
                }
                $ids = collect(explode(',', $this->option('ids')))->map(fn ($i) => (int) trim($i));
            } else {
                $ids = DB::table('events')->where('type', $depositType)->whereIn('source', ['backfill', 'rest', 'stream'])
                    ->selectRaw("DISTINCT CAST(JSON_EXTRACT(flow_metadata, '$.id') AS UNSIGNED) id")->pluck('id')->shuffle()->take($n);
                if (! $this->option('recent-only')) {
                    $ids = $ids->merge(DB::table($table)->whereNotNull('flow_address')->inRandomOrder()->limit($n)->pluck('flow_id'));
                }
            }
            $rows = DB::table($table)->whereIn('flow_id', $ids->unique()->values())->get(['flow_id', 'flow_address']);
            $ok = $bad = $none = $inPack = 0;
            foreach ($rows as $row) {
                if (! $row->flow_address) {
                    // Sportbits equipped on a Sportvatar, inside a pack or burned have no wallet owner.
                    $none++;

                    continue;
                }
                try {
                    $out = json_decode(Flow::script($script)->argAddress($row->flow_address)->argInt($row->flow_id)->run(), true);
                    $found = is_array($out) && ($out['value'] ?? false) === true;
                } catch (\Throwable $e) {
                    $found = false;
                }
                if ($found) {
                    $ok++;
                } elseif ($table === 'sportbits' && strtolower($row->flow_address) === $contract) {
                    // Recorded at the contract account but not in its collection: inside an unopened pack.
                    $inPack++;
                } else {
                    $bad++;
                    $this->warn("  $table #{$row->flow_id}: not held by {$row->flow_address} on chain");
                }
            }
            $this->info("$table: checked ".($ok + $bad + $inPack)." owners, $ok match, $bad mismatch, $inPack inside unopened packs, $none without wallet owner (equipped/burned)");
            $totals['in_pack'] += $inPack;
            $totals['ok'] += $ok;
            $totals['mismatch'] += $bad;
            $totals['no_owner'] += $none;
        }
        $this->line(json_encode($totals));

        return $totals['mismatch'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
