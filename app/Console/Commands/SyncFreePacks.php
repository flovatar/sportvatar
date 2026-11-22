<?php

namespace App\Console\Commands;

use App\Models\Pack;
use App\Services\FlowRest;
use Illuminate\Console\Command;

/**
 * Pack codes v2: make the DB list of claimable packs match the chain before handing out authorisations
 * (the DB under-reports claims since 2024). Packs no longer in the contract account get flow_address = 'claimed'.
 */
class SyncFreePacks extends Command
{
    protected $signature = 'sportvatar:sync-free-packs {--dry-run}';
    protected $description = 'Sync which packs are still in the contract account (read-only chain query)';

    public function handle()
    {
        $contract = config('services.flow.address');
        $cadence = "import SportvatarPack from $contract\naccess(all) fun main(): [UInt64] { return SportvatarPack.getPacks(address: $contract) ?? [] }";
        $onChain = array_map('intval', FlowRest::script($cadence));
        $set = array_flip($onChain);
        $this->info(count($onChain) . ' packs in the contract account on-chain');
        $stale = 0;
        Pack::whereFlowAddress($contract)->whereNotNull('flow_id')->chunkById(1000, function ($packs) use ($set, &$stale) {
            foreach ($packs as $p) {
                if (!isset($set[(int) $p->flow_id])) {
                    $stale++;
                    if (!$this->option('dry-run')) {
                        $p->update(['flow_address' => 'claimed']);
                    }
                }
            }
        });
        $this->info("$stale packs marked as no longer claimable" . ($this->option('dry-run') ? ' (dry run)' : ''));
        return 0;
    }
}
