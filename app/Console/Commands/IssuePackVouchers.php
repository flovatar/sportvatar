<?php

namespace App\Console\Commands;

use App\Models\PackVoucher;
use App\Services\PackAuthorizer;
use Illuminate\Console\Command;

/**
 * Pack codes v2: issue free-pack vouchers.
 *
 *  Address-bound (no code; the wallet just logs in and claims), e.g. the Flovatar-holder packs:
 *    php artisan sportvatar:issue-pack-vouchers flovatar_holder --addresses=storage/app/private/holders.csv --batch=2026-11-reissue-holders
 *    (CSV: address[,legacy_pack_flow_id] per line)
 *
 *  Bearer codes (Gazzetta / giveaways): codes are printed ONCE to --out (chmod 600) and only their HMAC is stored.
 *    php artisan sportvatar:issue-pack-vouchers gazzetta --count=3000 --out=/secure/path/gazzetta-codes.csv --batch=2026-11-reissue-gazzetta
 *
 * A redeemed code is exchanged for an on-chain authorisation bound to the redeeming wallet (PackClaimController).
 */
class IssuePackVouchers extends Command
{
    protected $signature = 'sportvatar:issue-pack-vouchers {channel} {--count=0} {--addresses=} {--out=} {--batch=}';
    protected $description = 'Issue v2 free-pack vouchers (address-bound or bearer codes)';

    public function handle()
    {
        $channel = $this->argument('channel');
        $batch = $this->option('batch') ?: $channel . '-' . now()->format('YmdHis');

        if ($file = $this->option('addresses')) {
            $n = 0;
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $cols = str_getcsv($line);
                if (!preg_match('/^(0x)?[0-9a-fA-F]{1,16}$/', trim($cols[0]))) {
                    continue; // header or junk
                }
                PackVoucher::create([
                    'channel' => $channel,
                    'batch' => $batch,
                    'assigned_address' => PackAuthorizer::normalizeAddress($cols[0]),
                    'legacy_pack_flow_id' => $cols[1] ?? null,
                ]);
                $n++;
            }
            $this->info("Issued $n address-bound vouchers ($channel, batch $batch)");
            return 0;
        }

        $count = (int) $this->option('count');
        $out = $this->option('out');
        if ($count < 1 || !$out) {
            $this->error('Give --addresses=<csv> or --count=N --out=<file>');
            return 1;
        }
        if (file_exists($out)) {
            $this->error("$out exists: refusing to overwrite");
            return 1;
        }
        PackAuthorizer::hashCode('check-pepper'); // fails early if PACK_CODE_PEPPER is missing
        $old = umask(0077);
        $fh = fopen($out, 'x');
        umask($old);
        fwrite($fh, "code,channel,batch\n");
        for ($i = 0; $i < $count; $i++) {
            do {
                $code = PackAuthorizer::newCode();
                $hash = PackAuthorizer::hashCode($code);
            } while (PackVoucher::where('code_hash', $hash)->exists());
            PackVoucher::create(['channel' => $channel, 'batch' => $batch, 'code_hash' => $hash]);
            fwrite($fh, "$code,$channel,$batch\n");
        }
        fclose($fh);
        chmod($out, 0600);
        $this->info("Issued $count codes ($channel, batch $batch). Codes written ONCE to $out (chmod 600): distribute, then delete it.");
        return 0;
    }
}
