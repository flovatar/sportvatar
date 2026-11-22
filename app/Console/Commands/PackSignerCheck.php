<?php

namespace App\Console\Commands;

use App\Services\FlowRest;
use App\Services\PackAuthorizer;
use Illuminate\Console\Command;

/** Pack codes v2: check that the configured signer key and epoch match the SportvatarPack pack verifier on-chain. */
class PackSignerCheck extends Command
{
    protected $signature = 'sportvatar:pack-signer-check';
    protected $description = 'Compare the local pack-signer key / epoch with the on-chain verifier config';

    public function handle(PackAuthorizer $authorizer)
    {
        $contract = config('services.flow.address');
        $cfg = FlowRest::script("import SportvatarPack from $contract\naccess(all) fun main(): SportvatarPack.VerifierConfig { return SportvatarPack.getVerifierConfig() }");
        $local = $authorizer->publicKeyHex();
        $this->line('local public key   : ' . $local);
        $this->line('on-chain public key: ' . ($cfg['signerPublicKey'] ?? 'none'));
        $this->line('epoch local/chain  : ' . config('packcodes.epoch') . ' / ' . $cfg['epoch']);
        $this->line('claims / purchases : ' . json_encode($cfg['claimsEnabled']) . ' / ' . json_encode($cfg['purchasesEnabled']));
        $ok = strtolower((string) ($cfg['signerPublicKey'] ?? '')) === $local && (int) $cfg['epoch'] === (int) config('packcodes.epoch');
        $ok ? $this->info('OK: signer and epoch match') : $this->error('MISMATCH: fix PACK_SIGNER_KEY_PATH / PACK_EPOCH or the on-chain config');
        return $ok ? 0 : 1;
    }
}
