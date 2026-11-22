<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\Pack;
use App\Models\Sale;
use App\Models\SaleOffer;
use Carbon\Carbon;


class ProcessEvents extends BaseJob
{


    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
        parent::__construct();
    }



    public function getName(){
        return 'ProcessEvents';
    }





    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {

        //$events = Event::whereSynced(false)->whereGraffle(false)->orderBy('block_id', 'asc')->orderBy('index', 'asc')->get();

        $event = $this->event;

        $check = false;


        if($event->type == $this->getEventAddress('SportvatarCreated')){
            if(isset($event->flow_metadata['metadata']) && isset($event->flow_metadata['metadata']['creatorAddress'])) {
                $newSportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['metadata']['creatorAddress']);
                if($newSportvatar){
                    $check = true;
                    $this->updateTemplatesBurnCount($newSportvatar);
                }
            } else {
                info('Sportvatar Created event skipped because creator address was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarDeposit')){
            if(isset($event->flow_metadata['to']) && $event->flow_metadata['to']) {
                $sportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['to']);
                if (!$sportvatar) {
                    $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
                }
                if ($sportvatar) {
                    $sportvatar->update(['flow_address' => $event->flow_metadata['to']]);
                    $check = true;
                } else {
                    info('Sportvatar Deposit event skipped because Sportvatar was not found!!!!');
                }
            } else {
                info('Sportvatar Deposit event skipped because sent to null!!!!');
                $this->event->delete();
                return;
            }
        } else if($event->type == $this->getEventAddress('SportvatarUpdated')){
            $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
            if($sportvatar) {
                $this->syncSportvatarFromFlowAddress($sportvatar->flow_id, $sportvatar->flow_address);
                $check = true;
            } else {
                info('Sportvatar Updated event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportbitCreated')){
            if(isset($event->flow_metadata['id']) && isset($event->flow_metadata['templateId'])) {
                $this->syncSportbitFromFlow($event->flow_metadata['id'], $event->flow_metadata);
                $check = true;
            } else {
                info('Sportvatar Component Created event skipped because template was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportbitDeposit')){
            if(isset($event->flow_metadata['to']) && $event->flow_metadata['to']) {
                $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
                if (!$sportvatar) {
                    $sportvatar = $this->syncSportbitFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['to']);
                }
                if ($sportvatar) {
                    $sportvatar->update(['flow_address' => $event->flow_metadata['to']]);
                    $check = true;
                } else {
                    info('Sportvatar Component Deposit event skipped because Component was not found!!!!');
                }
            } else {
                info('Sportvatar Component Deposit event skipped because sent to null!!!!');
                $this->event->delete();
                return;
            }
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
        } else if($event->type == $this->getEventAddress('SportvatarTemplateCreated')){
            if(isset($event->flow_metadata['id'])) {
                info('Sportvatar Template Created start!!!!');
                $this->syncSportvatarTemplateFromFlowId($event->flow_metadata['id']);
                $check = true;
            } else {
                info('Sportvatar Template Created event skipped because template was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarPackCreated')){
            $pack = Pack::where('secret', 'like', $event->flow_metadata['prefix'].'%')->whereNull('flow_address')->whereNull('flow_id')->whereNull('flow_metadata')->orderBy('id', 'asc')->first();
            if($pack) {
                $pack->update([
                    'flow_id' => $event->flow_metadata['id'],
                    'flow_address' => config('services.flow.address'),
                    'flow_metadata' => $event->flow_metadata
                ]);
                $check = true;
                //$this->syncSportvatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            } else {
                info('Sportvatar Pack Created event skipped because the unassigned Pack was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarPackDeposit')){
            $pack = $this->syncSportvatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            if($pack){
                $pack->update(['flow_address' => $event->flow_metadata['to']]);
                // pack codes v2: a deposit to the redeeming wallet completes its voucher
                \App\Models\PackVoucher::markClaimed($event->flow_metadata['id'], $event->flow_metadata['to']);
                $check = true;
            } else {
                info('Sportvatar Pack Deposit event skipped because Pack was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarPackOpened')){
            $pack = $this->syncSportvatarPackFromFlow($event->flow_metadata['id'], $event->flow_metadata);
            if($pack) {
                $pack->update(['opened' => 1]);
                $check = true;
            } else {
                info('Sportvatar Pack Opened event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarSaleCreated') || $event->type == $this->getEventAddress('SportvatarSalePrice')){
            $sportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['address']);
            if($sportvatar) {
                $price = ($event->type == $this->getEventAddress('SportvatarSalePrice')) ? $event->flow_metadata['newPrice'] : $event->flow_metadata['price'];
                SaleOffer::updateOrCreate(
                    [
                        'item_id' => $sportvatar->id,
                        'item_type' => 'App\Models\Sportvatar',
                        'flow_id' => $event->flow_metadata['id'],
                        'flow_address' => $event->flow_metadata['address']
                    ],
                    [
                        'flow_metadata' => $sportvatar->flow_metadata,
                        'price' => $price,
                        'is_completed' => false
                    ]
                );
                if (!$sportvatar->flow_address) {
                    $sportvatar->update(['flow_address' => $event->flow_metadata['address']]);
                }
                $check = true;
            } else {
                info('SaleCreated event skipped because Sportvatar was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarSaleRemoved')){
            SaleOffer::isSportvatar()->whereFlowId($event->flow_metadata['tokenId'])->whereFlowAddress($event->flow_metadata['address'])->delete();
            $check = true;

        } else if($event->type == $this->getEventAddress('SportvatarSaleCompleted')){

            //Disabling it because redundant with SaleRemoved
            //SaleOffer::isSportvatar()->whereFlowId($event->flow_metadata['id'])->whereFlowAddress($event->flow_metadata['from'])->delete();

            $sportvatar = $this->syncSportvatarFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['from']);
            if (!$sportvatar) {
                $saleOffer = SaleOffer::isSportvatar()->whereFlowId($event->flow_metadata['id'])->whereFlowAddress($event->flow_metadata['from'])->first();
                if($saleOffer){
                    $sportvatar = $saleOffer->item;
                }
            }
            if(!$sportvatar && $event->flow_metadata['id'] != null){
                $sportvatar = Sportvatar::whereFlowId($event->flow_metadata['id'])->first();
            }
            if($sportvatar){
                Sale::firstOrCreate(
                    [
                        'item_id' => $sportvatar->id,
                        'item_type' => 'App\Models\Sportvatar',
                        'price' => $event->flow_metadata['price'],
                        'flow_buyer_address' => $event->flow_metadata['to'],
                        'flow_seller_address' => $event->flow_metadata['from']
                    ],
                    [
                        'completed_at' => Carbon::now()
                    ]
                );
                $check = true;

                //Disabling it because redundant with WebshotDeposit
                //$webshot->update(['flow_address' => $event->flow_metadata['to']]);
            } else {
                info('Sale Completed event skipped because Sportvatar was not found!!!!');
            }

        } else if($event->type == $this->getEventAddress('ComponentSaleCreated') || $event->type == $this->getEventAddress('ComponentSalePrice')){
            $component = $this->syncSportbitFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['address']);
            if($component) {
                $price = ($event->type == $this->getEventAddress('ComponentSalePrice')) ? $event->flow_metadata['newPrice'] : $event->flow_metadata['price'];
                SaleOffer::updateOrCreate(
                    [
                        'item_id' => $component->id,
                        'item_type' => 'App\Models\Sportbit',
                        'flow_id' => $event->flow_metadata['id'],
                        'flow_address' => $event->flow_metadata['address']
                    ],
                    [
                        'flow_metadata' => $component->flow_metadata,
                        'price' => $price,
                        'is_completed' => false
                    ]
                );
                if (!$component->flow_address) {
                    $component->update(['flow_address' => $event->flow_metadata['address']]);
                }
                $check = true;

            } else {
                info('SaleCreated event skipped because Component was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('ComponentSaleRemoved')){
            SaleOffer::isComponent()->whereFlowId($event->flow_metadata['tokenId'])->whereFlowAddress($event->flow_metadata['address'])->delete();
            $check = true;
        } else if($event->type == $this->getEventAddress('ComponentSaleCompleted')){

            //Disabling it because redundant with SaleRemoved
            //SaleOffer::isComponent()->whereFlowId($event->flow_metadata['id'])->whereFlowAddress($event->flow_metadata['from'])->delete();

            $sportvatar = $this->syncSportbitFromFlowAddress($event->flow_metadata['id'], $event->flow_metadata['from']);
            if (!$sportvatar) {
                $saleOffer = SaleOffer::isComponent()->whereFlowId($event->flow_metadata['id'])->whereFlowAddress($event->flow_metadata['from'])->first();
                if($saleOffer){
                    $sportvatar = $saleOffer->item;
                }
            }
            if(!$sportvatar && $event->flow_metadata['id'] != null){
                $sportvatar = Sportbit::whereFlowId($event->flow_metadata['id'])->first();
            }
            if($sportvatar){
                Sale::firstOrCreate(
                    [
                        'item_id' => $sportvatar->id,
                        'item_type' => 'App\Models\Sportbit',
                        'price' => $event->flow_metadata['price'],
                        'flow_buyer_address' => $event->flow_metadata['to'],
                        'flow_seller_address' => $event->flow_metadata['from']
                    ],
                    [
                        'completed_at' => Carbon::now()
                    ]
                );
                $check = true;

                //Disabling it because redundant with WebshotDeposit
                //$webshot->update(['flow_address' => $event->flow_metadata['to']]);
            } else {
                info('SaleCompleted event skipped because Component was not found!!!!');
            }
        } else if($event->type == $this->getEventAddress('SportvatarWithdraw')){
            //No need to do anything in this case
            $check = true;
            info('SportvatarWithdraw event skipped because not needed!!!!');
        } else {
            info('Event skipped because not parsed!!!!  '.$event->type);
            return;
        }

        if($check){
            $this->event->update(['synced' => true]);
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
