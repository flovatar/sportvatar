<?php

namespace App\Console\Commands;

use App\Models\PackVoucher;
use App\Services\Flow\FlowEventTypes;
use App\Services\FlowRest;
use Illuminate\Console\Command;

/**
 * Pack codes v2: POST /pack/authorize-claim marks a voucher 'reserved' with a pack and an on-chain authorisation that
 * expires after PACK_AUTH_TTL (15 min). If the wallet never signs (closed popup, Dapper refusing an unapproved tx...)
 * the voucher stayed 'reserved' for ever. This puts such vouchers back to 'issued' once the authorisation has expired
 * (+ a grace period) AND the chain shows the reserved pack still in the Sportvatar account's pack collection, so a claim
 * that did go through (but the indexer hasn't applied yet) is never released. If the pack is held by the redeeming
 * wallet, the voucher is marked claimed instead.
 */
class ReleaseVoucherReservations extends Command
{
    protected $signature = 'sportvatar:release-voucher-reservations
        {--grace=900 : seconds after the authorisation expiry before a reservation is released}
        {--dry-run : report only}';

    protected $description = 'Return expired, unclaimed free-pack voucher reservations to issued';

    public function handle(): int
    {
        $cutoff = time() - max(0, (int) $this->option('grace'));
        $vouchers = PackVoucher::where('status', 'reserved')
            ->where(fn ($q) => $q->whereNull('authorization_expiry')->orWhere('authorization_expiry', '<', $cutoff))
            ->orderBy('id')->limit(100)->get(); // a few per run; the schedule repeats
        if ($vouchers->isEmpty()) {
            $this->info('No expired reservations');

            return self::SUCCESS;
        }

        $contract = '0x'.FlowEventTypes::contractAddress();
        $packIds = $vouchers->pluck('pack_flow_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $redeemers = $vouchers->pluck('redeemed_by')->filter()->unique()->values()->all();
        $script = <<<CDC
import SportvatarPack from $contract

// For each pack id: who holds it among the contract account and the given wallets ("" = nobody of them / opened)
access(all) fun main(packOwner: Address, wallets: [Address], ids: [UInt64]): {UInt64: Address} {
    let out: {UInt64: Address} = {}
    var owners: [Address] = [packOwner]
    owners.appendAll(wallets)
    for owner in owners {
        if let col = getAccount(owner).capabilities.borrow<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath) {
            let have = col.getIDs()
            for id in ids {
                if have.contains(id) { out[id] = owner }
            }
        }
    }
    return out
}
CDC;
        try {
            $holders = $packIds ? FlowRest::script($script, [
                ['type' => 'Address', 'value' => $contract],
                ['type' => 'Array', 'value' => array_map(fn ($a) => ['type' => 'Address', 'value' => $a], $redeemers)],
                ['type' => 'Array', 'value' => array_map(fn ($id) => ['type' => 'UInt64', 'value' => (string) $id], $packIds)],
            ]) : [];
        } catch (\Throwable $e) {
            $this->error('Chain check failed, nothing changed: '.substr($e->getMessage(), 0, 200));

            return self::FAILURE;
        }
        $holders = array_change_key_case(array_map('strtolower', (array) $holders));

        $released = $claimed = $left = 0;
        foreach ($vouchers as $v) {
            $holder = $v->pack_flow_id ? ($holders[(string) $v->pack_flow_id] ?? null) : $contract;
            if ($holder === $contract) {
                $this->line("voucher {$v->id}: pack {$v->pack_flow_id} still unclaimed -> issued");
                $released++;
                if (! $this->option('dry-run')) {
                    $v->update(['status' => 'issued', 'pack_flow_id' => null, 'redeemed_by' => null,
                        'authorization_expiry' => null, 'authorization_epoch' => null]);
                }
            } elseif ($holder !== null && $holder === strtolower((string) $v->redeemed_by)) {
                $this->line("voucher {$v->id}: pack {$v->pack_flow_id} is in {$holder} -> claimed");
                $claimed++;
                if (! $this->option('dry-run')) {
                    $v->update(['status' => 'claimed', 'claimed_at' => now()]);
                }
            } else {
                // opened or elsewhere: leave it for a human (the indexer normally marks it claimed on the deposit)
                $this->warn("voucher {$v->id}: pack {$v->pack_flow_id} not at the contract nor at {$v->redeemed_by}: left reserved");
                $left++;
            }
        }
        $msg = ($this->option('dry-run') ? '[dry-run] ' : '')."released {$released}, marked claimed {$claimed}, left {$left}";
        $this->info($msg);
        if ($released + $claimed > 0 && ! $this->option('dry-run')) {
            info('sportvatar:release-voucher-reservations '.$msg);
        }

        return self::SUCCESS;
    }
}
