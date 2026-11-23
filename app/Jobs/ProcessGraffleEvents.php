<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\Pack;
use App\Models\Sale;
use App\Models\SaleOffer;
use App\Models\PackVoucher;
use App\Services\Flow\FlowEventTypes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ProcessGraffleEvents extends BaseJob
{

    protected $event;
    protected $isGraffle;

    public function __construct($event, $isGraffle = true)
    {
        $this->event = $event;
        $this->isGraffle = $isGraffle;
        parent::__construct();
    }


    public function getName(){
        return 'ProcessGraffleEvents';
    }


    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {

        $event = $this->event;

        $check = false;

        if($event->type == $this->getEventAddress('SportvatarSeriesCreated')){
            if(isset($event->flow_metadata['id'])) {
                info('Sportvatar Series Created start!!!!');
                $this->syncSeriesFromFlowId($event->flow_metadata['id'], true);
                $check = true;
            } else {
                info('Sportvatar Series Created event skipped because series was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarTemplateCreated')){
            if(isset($event->flow_metadata['id'])) {
                info('Sportvatar Template Created start!!!!');
                $this->syncSportvatarTemplateFromFlowId($event->flow_metadata['id'], true);
                $check = true;
            } else {
                info('Sportvatar Template Created event skipped because template was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarCreated')){
            if(isset($event->flow_metadata['address'])) {
                $newSportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['address']);
                if($newSportvatar){
                    $check = true;
                    if(!$newSportvatar->flow_address) {
                        $newSportvatar->update([
                            'flow_address' => $event->flow_metadata['address']
                        ]);
                    }
                    $this->updateTemplatesBurnCount($newSportvatar);
                    ExportAllImages::dispatch($event->flow_metadata['id']);
                } else {
                    info('Sportvatar Created event failed because creator address was not found!!!!');
                    info(print_r($newSportvatar,true));
                }
            } else {
                info('Sportvatar Created event skipped because creator address was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportbitCreated')){
            if(isset($event->flow_metadata['metadata']) && isset($event->flow_metadata['metadata']['id'])) {
                $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
                if (!$sportvatar) {
                    $sportvatar = $this->syncSportbitFromFlowAddress($event->flow_metadata['id'], config('sportvatar.contractAddress'));
                }
                if (!$sportvatar) {
                    info('Sportvatar Component Created event skipped because Component was not found!!!!');
                }
            } else if(isset($event->flow_metadata['id']) && isset($event->flow_metadata['templateId'])) {
                $this->syncSportbitFromFlow($event->flow_metadata['id'], $event->flow_metadata);
                $check = true;
            } else {
                info('Sportvatar Component Created event skipped because creator address was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarDeposit')){
            $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
            if(!$sportvatar){
                $sportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['to']);
            }
            if($sportvatar){
                $sportvatar->update(['flow_address' => $event->flow_metadata['to']]);
                $this->cleanupSales($event->flow_metadata['id'], $event->flow_metadata['to'], 'sportvatar');
                $check = true;
            } else {
                info('Sportvatar Deposit event skipped because Sportvatar was not found!!!!');
            }
        } else if(in_array($event->type, [$this->getEventAddress('SportvatarUpdated'), FlowEventTypes::type('Sportvatar.NameSet'), FlowEventTypes::type('Sportvatar.PositionChanged'), FlowEventTypes::type('Sportvatar.StoryAdded')], true)){
            $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
            if($sportvatar) {
                if($this->syncSportvatarFromFlowAddress($sportvatar->flow_id, $sportvatar->flow_address)){
                    $check = true;
                }
                ExportAllImages::dispatch($event->flow_metadata['id']);
            } else {
                info('Sportvatar Updated event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportbitDeposit')){
            if(isset($event->flow_metadata['to']) && $event->flow_metadata['to']) {
                $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
                if (!$sportvatar) {
                    $sportvatar = $this->syncSportbitFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['to']);
                }
                if ($sportvatar) {
                    $sportvatar->update(['flow_address' => $event->flow_metadata['to']]);
                    $this->cleanupSales($event->flow_metadata['id'], $event->flow_metadata['to'], 'component');
                    $check = true;
                } else {
                    info('Sportvatar Component Deposit event skipped because Component was not found!!!!');
                }
            } else {
                info('Sportvatar Component Deposit event skipped because sent to null!!!!');
                $this->event->delete();
                return;
            }
            //info(print_r($event->flow_metadata,true));
        } else if($event->type == $this->getEventAddress('SportbitWithdraw')){
            $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
            if($sportvatar) {
                $sportvatar->update(['flow_address' => null]);
                $check = true;
            } else {
                info('Sportvatar Component Withdraw event skipped because Component was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportbitDestroyed')){
            info('Sportvatar Component Destroyed check start!!!!');
            $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
            if($sportvatar) {
                $sportvatar->update(['destroyed' => 1]);
                $check = true;
            } else {
                info('Sportvatar Component Destroyed event skipped because Component was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('FlovatarPackCreated') && $this->isGraffle === false){
            $pack = Pack::where('secret', 'like', $event->flow_metadata['prefix'].'%')->whereNull('flow_address')->whereNull('flow_id')->whereNull('flow_metadata')->orderBy('id', 'asc')->first();
            if($pack) {
                $pack->update([
                    'flow_id' => $event->flow_metadata['id'],
                    'flow_address' => config('services.flow.address'),
                    'flow_metadata' => $event->flow_metadata
                ]);
                $check = true;
                //$this->syncFlovatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            } else {
                info('Sportvatar Pack Created event skipped because the unassigned Pack was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarPackDeposit')){
            $pack = Pack::whereFlowId($event->flow_metadata['id'])->first();
            if(!$pack){
                $pack = $this->syncSportvatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            }
            if($pack){
                $pack->update(['flow_address' => $event->flow_metadata['to']]);
                if(!empty($event->flow_metadata['to'])){
                    // pack codes v2: a deposit to the redeeming wallet completes its voucher
                    PackVoucher::markClaimed($event->flow_metadata['id'], $event->flow_metadata['to']);
                }
                $check = true;
            } else {
                info('Sportvatar Pack Deposit event skipped because Pack was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarPackOpened')){
            $pack = Pack::whereFlowId($event->flow_metadata['id'])->first();
            if(!$pack){
                $pack = $this->syncSportvatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            }
            if($pack) {
                $pack->update(['opened' => 1]);
                $check = true;
            } else {
                info('Sportvatar Pack Opened event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCreated') && $event->flow_metadata['nftType']['typeID'] == $this->getSportvatarNFTAddress()){
            $isDapper = false;
            $nftId = null;
            $nftAddress = null;
            $price = null;
            $listingId = null;
            if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCreated')){
                //DAPPER
                info('DAPPER Sportvatar ON SALE!');
                $nftId = $event->flow_metadata['nftID'];
                $nftAddress = $event->flow_metadata['storefrontAddress'];
                $price = $event->flow_metadata['salePrice'];
                $listingId = $event->flow_metadata['listingResourceID'];
                $isDapper = true;
            } else {
                $nftId = $event->flow_metadata['id'];
                $nftAddress = $event->flow_metadata['address'];
                $price = $event->flow_metadata['price'];
            }
            // DUC (Dapper Wallet, USD) or FLOW (Flow Wallet) listing; events without the field are legacy DUC
            $currency = isset($event->flow_metadata['salePaymentVaultType']) ? $this->getStorefrontCurrency($event->flow_metadata) : 'DUC';
            $isDapper = ($currency === 'DUC');


            $sportvatar = Sportvatar::whereFlowId($nftId)->first();
            if(!$sportvatar){
                $sportvatar = $this->syncSportvatarFromFlowAddress($nftId, $nftAddress);
            }
            if($sportvatar && $currency === null) {
                info('Sportvatar listing '.$listingId.' skipped: unsupported payment vault');
                $check = true;
            } else if($sportvatar) {
                if($price <= 999999) {
                    SaleOffer::updateOrCreate(
                        [
                            'item_id' => $sportvatar->id,
                            'item_type' => 'App\Models\Sportvatar',
                            'flow_id' => $nftId
                        ],
                        [
                            'flow_metadata' => $sportvatar->flow_metadata,
                            'flow_address' => $nftAddress,
                            'price' => $price,
                            'currency' => $currency,
                            'is_completed' => false,
                            'is_dapper' => $isDapper,
                            'listing_id' => $listingId
                        ]
                    );
                    $check = true;
                }
                if (!$sportvatar->flow_address) {
                    $sportvatar->update(['flow_address' => $nftAddress]);
                }
            } else {
                info('SaleCreated event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCompleted')  && $event->flow_metadata['nftType']['typeID'] == $this->getSportvatarNFTAddress()){

            $isDapper = false;
            $nftId = null;
            $nftAddress = null;
            $price = null;
            $listingId = null;
            $isPurchased = true;
            if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCompleted')){
                //DAPPER
                info('DAPPER SPORTVATAR SOLD!');
                $nftId = $event->flow_metadata['nftID'];
                $price = $event->flow_metadata['salePrice'];
                $listingId = $event->flow_metadata['listingResourceID'];
                $isDapper = true;
                $isPurchased = $event->flow_metadata['purchased'];
            } else {
                $nftId = $event->flow_metadata['id'];
                $price = $event->flow_metadata['price'];
            }

            $currency = isset($event->flow_metadata['salePaymentVaultType']) ? $this->getStorefrontCurrency($event->flow_metadata) : 'DUC';

            //Disabling it because redundant with SaleRemoved
            //SaleOffer::isSportvatar()->whereFlowId($event->flow_metadata['id'])->whereFlowAddress($event->flow_metadata['from'])->delete();
            $sportvatar = Sportvatar::whereFlowId($nftId)->first();
            // Only the offer of THIS listing (a newer listing of the same NFT must survive an old listing's completion)
            $offerQuery = function() use ($nftId, $listingId) {
                $q = SaleOffer::isSportvatar()->whereFlowId($nftId);
                if($listingId){
                    $q->where(function($w) use ($listingId){ $w->whereNull('listing_id')->orWhere('listing_id', $listingId); });
                }
                return $q;
            };
            $saleOffer = $offerQuery()->first();

            // NFTStorefrontV2 events carry no from/to; the seller is the listing owner.
            $nftAddress = $saleOffer ? $saleOffer->flow_address : null;
            if(!$sportvatar && $nftAddress){
                $sportvatar = $this->syncSportvatarFromFlowAddress($nftId, $nftAddress);
            }
            if (!$sportvatar) {
                if($saleOffer){
                    $sportvatar = $saleOffer->item;
                }
            }

            if($isDapper){
                $offerQuery()->delete();
            }

            if($sportvatar && $isPurchased && $currency !== null){
                // keyed on (item, transaction): re-processing the same event must not add a second sale
                Sale::firstOrCreate(
                    [
                        'item_id' => $sportvatar->id,
                        'item_type' => 'App\Models\Sportvatar',
                        'transaction_id' => $event->transaction_id,
                    ],
                    [
                        'price' => $price,
                        'flow_buyer_address' => ($isDapper) ? null : ($event->flow_metadata['to'] ?? null),
                        'flow_seller_address' => $nftAddress,
                        'is_dapper' => ($currency === 'DUC'),
                        'currency' => $currency,
                        'completed_at' => $event->event_date ?? Carbon::now()
                    ]
                );
                $check = true;

            } else if(!$isPurchased || $currency === null) {
                // listing cancelled/expired (or an unsupported currency): the offer row is gone, nothing else to do
                $check = true;
            } else {
                info('Sale Completed event skipped because Sportvatar was not found!!!!');
            }

        } else if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCreated')  && $event->flow_metadata['nftType']['typeID'] == $this->getComponentNFTAddress()){

            $isDapper = false;
            $nftId = null;
            $nftAddress = null;
            $price = null;
            $listingId = null;
            $mint = null;
            if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCreated')){
                //DAPPER
                info('DAPPER COMPONENT ON SALE!');
                $nftId = $event->flow_metadata['nftID'];
                $nftAddress = $event->flow_metadata['storefrontAddress'];
                $price = $event->flow_metadata['salePrice'];
                $listingId = $event->flow_metadata['listingResourceID'];
                $isDapper = true;
            } else {
                $nftId = $event->flow_metadata['id'];
                $nftAddress = $event->flow_metadata['address'];
                $price = $event->flow_metadata['price'];
                $mint = isset($event->flow_metadata['mint']) ? $event->flow_metadata['mint'] : null;
            }
            // DUC (Dapper Wallet, USD) or FLOW (Flow Wallet) listing; events without the field are legacy DUC
            $currency = isset($event->flow_metadata['salePaymentVaultType']) ? $this->getStorefrontCurrency($event->flow_metadata) : 'DUC';
            $isDapper = ($currency === 'DUC');


            $component = Sportbit::whereFlowId($nftId)->first();
            if (!$component) {
                $component = $this->syncSportbitFromFlowAddress($nftId, $nftAddress);
            }

            if($component && $currency === null) {
                info('Sportbit listing '.$listingId.' skipped: unsupported payment vault');
                $check = true;
            } else if($component) {

                if($price <= 999999) {
                    SaleOffer::updateOrCreate(
                        [
                            'item_id' => $component->id,
                            'item_type' => 'App\Models\Sportbit',
                            'flow_id' => $nftId
                        ],
                        [
                            'flow_metadata' => $component->flow_metadata,
                            'flow_address' => $nftAddress,
                            'price' => $price,
                            'currency' => $currency,
                            'is_completed' => false,
                            'is_dapper' => $isDapper,
                            'listing_id' => $listingId
                        ]
                    );
                    $check = true;
                }
                if (!$component->flow_address) {
                    $component->update(['flow_address' => $nftAddress]);
                }

                if (!$component->mint) {
                    if($mint){
                        $component->update(['mint' => $mint]);
                    } else {
                        $componentF = $this->parseFlowResponse($this->getSportbitSaleFromChain($nftId, $nftAddress));
                        if ($componentF && isset($componentF['metadata']['mint']) ) {
                            $component->update(['mint' => $componentF['metadata']['mint']]);
                        }
                    }
                }
            } else {
                info('SaleCreated event skipped because Component was not found!!!!');
            }

        } else if($event->type == $this->getStorefrontEventAddress('StorefrontSaleCompleted')   && $event->flow_metadata['nftType']['typeID'] == $this->getComponentNFTAddress()){

            $isDapper = false;
            $nftId = null;
            $nftAddress = null;
            $price = null;
            $listingId = null;
            $isPurchased = true;
            //DAPPER
            info('DAPPER COMPONENT SOLD!');
            $nftId = $event->flow_metadata['nftID'];
            $price = $event->flow_metadata['salePrice'];
            $listingId = $event->flow_metadata['listingResourceID'];
            $isPurchased = $event->flow_metadata['purchased'];
            $currency = isset($event->flow_metadata['salePaymentVaultType']) ? $this->getStorefrontCurrency($event->flow_metadata) : 'DUC';

            $component = Sportbit::whereFlowId($nftId)->first();
            // Only the offer of THIS listing (a newer listing of the same NFT must survive an old listing's completion)
            $offerQuery = function() use ($nftId, $listingId) {
                $q = SaleOffer::isComponent()->whereFlowId($nftId);
                if($listingId){
                    $q->where(function($w) use ($listingId){ $w->whereNull('listing_id')->orWhere('listing_id', $listingId); });
                }
                return $q;
            };
            $saleOffer = $offerQuery()->first();

            $nftAddress = ($saleOffer) ? $saleOffer->flow_address : '';
            if(!$component && $nftAddress){
                info('DAPPER COMPONENT SOLD - not found component!');
                $component = $this->syncSportbitFromFlowAddress($nftId, $nftAddress);
            }
            if (!$component && $saleOffer) {
                info('DAPPER COMPONENT SOLD - not found component 2!');
                $component = $saleOffer->item;
            }

            $offerQuery()->delete();


            if($component && $isPurchased && $currency !== null){
                info('DAPPER COMPONENT SOLD - create sale!');
                // keyed on (item, transaction): re-processing the same event must not add a second sale
                Sale::firstOrCreate(
                    [
                        'item_id' => $component->id,
                        'item_type' => 'App\Models\Sportbit',
                        'transaction_id' => $event->transaction_id,
                    ],
                    [
                        'price' => $price,
                        'flow_buyer_address' => null,
                        'flow_seller_address' => $nftAddress,
                        'is_dapper' => ($currency === 'DUC'),
                        'currency' => $currency,
                        'completed_at' => $event->event_date ?? Carbon::now()
                    ]
                );
                $check = true;

            } else if(!$isPurchased || $currency === null) {
                $check = true;
            } else {
                info('Sale Completed event skipped because Component was not found!!!!');
            }

        } else if($event->type == $this->getStorefrontEventAddress('StorefrontListingDestroyed')){
            // A storefront listing was removed (cancelled by the seller, or cleaned up after a purchase/expiry).
            // Listing resource ids are global uuids, so the offer row is matched by listing id only.
            $listingId = $event->flow_metadata['listingResourceID'] ?? null;
            if($listingId){
                $deleted = SaleOffer::where('listing_id', $listingId)->delete();
                if($deleted){
                    info('Storefront listing '.$listingId.' removed: '.$deleted.' offer(s) deleted');
                }
            }
            $check = true;
        } else if($event->type == $this->getEventAddress('SportvatarWithdraw')){
            //No need to do anything in this case
            $check = true;
            info('SportvatarWithdraw event skipped because not needed!!!!');
        } else if($event->type == $this->getEventAddress('SportvatarPackCreated') && $this->isGraffle === false && Pack::whereFlowId($event->flow_metadata['id'])->exists()){
            // already linked to its DB row (all S1/Genesis packs were imported with their flow ids)
            $check = true;
        } else if($event->type == $this->getEventAddress('SportvatarPackCreated') && $this->isGraffle === false){
            $pack = Pack::where('secret', 'like', $event->flow_metadata['prefix'].'%')->whereNull('flow_address')->whereNull('flow_id')->whereNull('flow_metadata')->orderBy('id', 'asc')->first();
            if($pack) {
                $pack->update([
                    'flow_id' => $event->flow_metadata['id'],
                    'flow_address' => config('services.flow.address'),
                    'flow_metadata' => $event->flow_metadata
                ]);
                $check = true;
                //$this->syncFlovatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            } else {
                info('Flovatar Pack Created event skipped because the unassigned Pack was not found!!!!');
            }
        } else if(in_array($event->type, [FlowEventTypes::type('SportvatarPack.Withdraw'), FlowEventTypes::type('SportvatarPack.Purchased'), FlowEventTypes::type('SportvatarPack.Claimed')], true)){
            // Informational: the following SportvatarPack.Deposit sets the new owner.
            $check = true;
        } else if($event->type == FlowEventTypes::type('Sportvatar.Destroyed')){
            $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
            if($sportvatar){
                $sportvatar->update(['flow_address' => null]);
            }
            $check = true;
        } else if($event->type == FlowEventTypes::type('SportvatarTemplate.IDsRestricted') || $event->type == FlowEventTypes::type('SportvatarTemplate.IDsUnrestricted')){
            // Mirror on-chain restrictions (PR #3 contract) into flagged_items so the UI hides them.
            $models = ['Sportvatar.NFT' => Sportvatar::class, 'Sportbit.NFT' => Sportbit::class, 'SportvatarPack.Pack' => Pack::class];
            $nftType = (string) ($event->flow_metadata['nftType'] ?? '');
            $model = null;
            foreach ($models as $suffix => $class) {
                if (str_ends_with($nftType, $suffix)) { $model = $class; }
            }
            $ids = array_map('intval', (array) ($event->flow_metadata['ids'] ?? []));
            if ($model && $ids) {
                if ($event->type == FlowEventTypes::type('SportvatarTemplate.IDsRestricted')) {
                    $now = now();
                    DB::table('flagged_items')->insertOrIgnore(array_map(fn ($id) => [
                        'item_type' => $model, 'flow_id' => $id, 'reason' => 'on-chain: '.substr((string) ($event->flow_metadata['reason'] ?? ''), 0, 200),
                        'address' => null, 'transaction_id' => $event->transaction_id, 'created_at' => $now, 'updated_at' => $now,
                    ], $ids));
                } else {
                    DB::table('flagged_items')->where('item_type', $model)->whereIn('flow_id', $ids)->delete();
                }
                Cache::forget('flagged-items-'.md5($model));
            }
            $check = true;
        } else if($event->type == FlowEventTypes::type('SportvatarTemplate.AddressRestricted') || $event->type == FlowEventTypes::type('SportvatarTemplate.AddressUnrestricted')){
            // Address restrictions are mirrored by SPORTVATAR_BLOCKED_ADDRESSES (env); log for the operator.
            info('SportvatarTemplate restrictions address event: '.$event->type.' '.($event->flow_metadata['address'] ?? ''));
            $check = true;
        } else {
            info('Event skipped because not parsed!!!!  '.$event->type);
            return;
        }





        if($check){
            $this->event->update(['synced' => true]);
        } else {
            //ProcessGraffleEvents::dispatch($this->event)->delay(now()->addMinutes(10));
        }



    }
}


/*
 *
            'SportvatarSaleCreated' => 'Marketplace.SportvatarForSale',
            'SportvatarSaleCompleted' => 'Marketplace.SportvatarPurchased',
            'SportvatarSalePrice' => 'Marketplace.SportvatarPriceChanged',
            'SportvatarSaleRemoved' => 'Marketplace.SportvatarSaleWithdrawn',
            'ComponentSaleCreated' => 'Marketplace.SportbitForSale',
            'ComponentSaleCompleted' => 'Marketplace.SportbitPurchased',
            'ComponentSalePrice' => 'Marketplace.SportbitPriceChanged',
            'ComponentSaleRemoved' => 'Marketplace.SportbitSaleWithdrawn',
            'SaleRoyalty' => 'Marketplace.RoyaltyPaid',
 */
