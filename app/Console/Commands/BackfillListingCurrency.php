<?php

namespace App\Console\Commands;

use App\Jobs\BaseJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-derives sale_offers.currency / sales.currency from the stored NFTStorefrontV2 events
 * (salePaymentVaultType of the ListingAvailable / ListingCompleted event with the same listing id or transaction).
 * The currency migration backfilled every existing row from is_dapper (all DUC); run this once to correct rows
 * whose event says FlowToken. Idempotent; --dry-run only reports.
 */
class BackfillListingCurrency extends Command
{
    protected $signature = 'sportvatar:backfill-listing-currency {--dry-run}';

    protected $description = 'Set sale_offers/sales currency (DUC or FLOW) from the stored storefront events';

    public function handle(): int
    {
        $resolver = new class(null) extends BaseJob {
            public function __construct($e) {}
            public function currency($meta) { return $this->getStorefrontCurrency($meta); }
        };
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $checked = 0;

        foreach (DB::table('sale_offers')->whereNotNull('listing_id')->get(['id', 'listing_id', 'currency']) as $offer) {
            $checked++;
            $event = DB::table('events')->where('type', 'like', '%.NFTStorefrontV2.ListingAvailable')
                ->whereRaw("json_extract(flow_metadata, '$.listingResourceID') = ?", [(int) $offer->listing_id])
                ->orderByDesc('id')->first(['flow_metadata']);
            if (!$event) {
                continue;
            }
            $currency = $resolver->currency(json_decode($event->flow_metadata, true));
            if ($currency && $currency !== $offer->currency) {
                $this->line("sale_offer {$offer->id} listing {$offer->listing_id}: {$offer->currency} -> {$currency}");
                $changed++;
                if (!$dry) {
                    DB::table('sale_offers')->where('id', $offer->id)->update(['currency' => $currency, 'is_dapper' => $currency === 'DUC']);
                }
            }
        }

        foreach (DB::table('sales')->whereNotNull('transaction_id')->get(['id', 'transaction_id', 'currency']) as $sale) {
            $checked++;
            $event = DB::table('events')->where('type', 'like', '%.NFTStorefrontV2.ListingCompleted')
                ->where('transaction_id', $sale->transaction_id)->orderByDesc('id')->first(['flow_metadata']);
            if (!$event) {
                continue;
            }
            $currency = $resolver->currency(json_decode($event->flow_metadata, true));
            if ($currency && $currency !== $sale->currency) {
                $this->line("sale {$sale->id} tx {$sale->transaction_id}: {$sale->currency} -> {$currency}");
                $changed++;
                if (!$dry) {
                    DB::table('sales')->where('id', $sale->id)->update(['currency' => $currency, 'is_dapper' => $currency === 'DUC']);
                }
            }
        }

        $this->info(($dry ? '[dry-run] ' : '')."checked {$checked}, changed {$changed}");
        return self::SUCCESS;
    }
}
