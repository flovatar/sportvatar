<?php

namespace App\Console\Commands;

use App\Models\Pack;
use Illuminate\Console\Command;

/**
 * Pack codes v2: after the on-chain update (v1 claimForFree/purchase disabled), drop the v1 secrets/signatures
 * so the database stops carrying them. They are useless on-chain after the update; this is data hygiene.
 */
class RetireV1PackSecrets extends Command
{
    protected $signature = 'sportvatar:retire-v1-pack-secrets {--force}';
    protected $description = 'Null v1 pack secrets and signatures (run only after the SportvatarPack update is live)';

    public function handle()
    {
        $n = Pack::whereNotNull('signature')->orWhereNotNull('secret')->count();
        if (!$this->option('force')) {
            $this->warn("$n packs still carry v1 secrets/signatures. Re-run with --force after the on-chain update (owner-kit S2).");
            return 1;
        }
        Pack::whereNotNull('signature')->orWhereNotNull('secret')->update(['signature' => null, 'secret' => null]);
        $this->info("Retired v1 secrets/signatures of $n packs.");
        return 0;
    }
}
