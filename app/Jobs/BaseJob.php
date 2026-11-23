<?php

namespace App\Jobs;

use App\Models\SaleOffer;
use App\Models\Sportvatar;
use App\Models\SportvatarTemplate;
use App\Models\Sportbit;
use App\Models\SportvatarSeries;
use App\Models\Pack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $eventsList;
    protected $eventsBaseList;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->eventsList = [
            'SportvatarSeriesCreated' => 'SportvatarTemplate.CreatedSeries',
            'SportvatarTemplateCreated' => 'SportvatarTemplate.Created',
            'SportbitCreated' => 'Sportbit.Created',
            'SportbitWithdraw' => 'Sportbit.Withdraw',
            'SportbitDeposit' => 'Sportbit.Deposit',
            'SportbitDestroyed' => 'Sportbit.Destroyed',
            'SportvatarPackCreated' => 'SportvatarPack.Created',
            'SportvatarPackDeposit' => 'SportvatarPack.Deposit',
            'SportvatarPackOpened' => 'SportvatarPack.Opened',
            'SportvatarCreated' => 'Sportvatar.Created',
            'SportvatarWithdraw' => 'Sportvatar.Withdraw',
            'SportvatarDeposit' => 'Sportvatar.Deposit',
            'SportvatarUpdated' => 'Sportvatar.Updated',
            'StorefrontSaleCreated' => 'NFTStorefrontV2.ListingAvailable',
            'StorefrontSaleCompleted' => 'NFTStorefrontV2.ListingCompleted',
            'StorefrontListingDestroyed' => 'NFTStorefrontV2.Listing.ResourceDestroyed',
        ];
        $this->eventsBaseList = [
            'SportvatarSeriesCreated' => 'SportvatarTemplate.CreatedSeries',
            'SportvatarTemplateCreated' => 'SportvatarTemplate.Created',
            'SportbitCreated' => 'Sportbit.Created',
            'SportvatarPackCreated' => 'SportvatarPack.Created',
        ];
    }

    public function getName(){
        return 'BaseJob';
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

    }

    protected function getSportvatarFromChain($id, $address)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_sportvatar.cdc')->argAddress($address)->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar!!!');
        }

        return $results;
    }

    protected function getSportbitFromChain($id, $address)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_component.cdc')->argAddress($address)->argInt($id)->run();
            info(print_r($results,true));
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar Component!!!');
        }

        return $results;
    }
    protected function getSportvatarSaleFromChain($id, $address)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_sportvatar_sale.cdc')->argAddress($address)->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar Sale!!!');
        }

        return $results;
    }

    protected function getSportbitSaleFromChain($id, $address)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_component_sale.cdc')->argAddress($address)->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar Component Sale!!!');
        }

        return $results;
    }

    protected function getSportvatarTemplateFromChain($id)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_template.cdc')->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar Template!!!');
        }

        return $results;
    }

    protected function getSportvatarPackFromChain($id, $address)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_pack.cdc')->argAddress($address)->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Sportvatar Pack!!!');
        }

        return $results;
    }

    protected function syncSportvatarFromFlow($id, $flowMeta){
        if(isset($flowMeta['id']) && isset($flowMeta['metadata'])) {
            $stats = $flowMeta['stats'];
            $sportvatar = Sportvatar::updateOrCreate(
                ['flow_id' => $id],
                [
                    'flow_metadata' => $flowMeta,
                    'name' => isset($flowMeta['name']) ? $flowMeta['name'] : null,
                    'mint' => $flowMeta['mint'],
                    'svg' => $flowMeta['svg'],
                    'sportvatar_series_id' => $flowMeta['series'],
                    'rarity' => isset($flowMeta['rarity']) ? $flowMeta['rarity'] : 'common',
                    'ability' => intval($stats['power']) + intval($stats['endurance']) + intval($stats['mental strength']) + intval($stats['speed']) + intval($stats['technique'])
                ]);
            info('Updated Sportvatar with Flow data! ' . $sportvatar->id);
            return $sportvatar;
        } else {
            return null;
        }
    }
    protected function syncSportvatarFromFlowAddress($id, $address)
    {

        $sportvatarF = $this->parseFlowResponse($this->getSportvatarFromChain($id, $address));
        if($sportvatarF && isset($sportvatarF['id']) && isset($sportvatarF['metadata'])){
            return $this->syncSportvatarFromFlow($sportvatarF['id'], $sportvatarF);
        } else {
            $sportvatarF = $this->parseFlowResponse($this->getSportvatarSaleFromChain($id, $address));
            if($sportvatarF && isset($sportvatarF['id']) && isset($sportvatarF['metadata'])){
                return $this->syncSportvatarFromFlow($sportvatarF['id'], $sportvatarF);
            }
        }

        info('Unable to update Sportvatar because it was not found on chain!');
        return null;
    }

    protected function syncSportbitFromFlow($id, $flowMeta){
        $sportvatarTemplate = SportvatarTemplate::whereFlowId($flowMeta['templateId'])->first();
        if(!$sportvatarTemplate){
            info('Unable to update Sportvatar Component because the Template was not found!');
            return null;
        }
        $sportvatarComponent =  Sportbit::updateOrCreate(
            [
                'flow_id' => $id,
                'sportvatar_template_id' => $sportvatarTemplate->id
            ],
            [
                'flow_metadata' => $flowMeta,
            ]
        );
        //'flow_address' => config('services.flow.address'),

        if($flowMeta['mint']){
            $sportvatarComponent->update(['mint' => $flowMeta['mint']]);
        }
        if(!$sportvatarComponent->flow_address){
            $sportvatarComponent->update(['flow_address' => config('services.flow.address')]);
        }
        //info('Updated Sportvatar Component with Flow data! ' . $sportvatarComponent->id);
        return $sportvatarComponent;
    }
    protected function syncSportbitFromFlowAddress($id, $address)
    {
        if($id && $address) {
            $component = Sportbit::whereFlowId($id)->whereFlowAddress($address)->first();
            if ($component) {
                return $component;
            } else {
                $componentF = $this->parseFlowResponse($this->getSportbitFromChain($id, $address));
                //info('---------------- GET SPORTVATAR COMPONENT FROM CHAIN ------------');
                //info(print_r($componentF,true));
                if ($componentF && isset($componentF['id']) && isset($componentF['templateId'])) {
                    return $this->syncSportbitFromFlow($componentF['id'], $componentF);
                } else {
                    $componentF = $this->parseFlowResponse($this->getSportbitSaleFromChain($id, $address));
                    if ($componentF && isset($componentF['id']) && isset($componentF['templateId'])) {
                        return $this->syncSportbitFromFlow($componentF['id'], $componentF);
                    }
                }
            }
        } else {
            info('Sync skipped because address or id is null!');
            return null;
        }
        info('Unable to update Sportvatar Component because it was not found on chain!');
        //info($id);
        //info($address);
        return null;
    }


    protected function syncSportvatarTemplateFromFlow($id, $flowMeta){
        $sportvatarTemplate = SportvatarTemplate::whereFlowId($id)->first();
        $sportvatarSeries = SportvatarSeries::whereFlowId($flowMeta['series'])->first();
        if(!$sportvatarSeries) {
            info('Collectible Template Created - SERIES NOT FOUND!!!!!!!!!');
        }
        $seriesId = $sportvatarSeries ? $sportvatarSeries->id : $flowMeta['series'];
        if(!$sportvatarTemplate){
            info('Sportvatar Template Created - get by name, layer and color!!!!');
            $sportvatarTemplate = SportvatarTemplate::whereNull('flow_id')->where('name', 'like', $flowMeta['name'])->where('rarity', 'like', $flowMeta['rarity'])->where('sportvatar_series_id', 'like', $seriesId)->where('layer', 'like', $flowMeta['layer'])->first();
        } else {
            info('Sportvatar Template Created - found by ID!!!!');
        }
        if($sportvatarTemplate){
            info('Sportvatar Template Created - found template!!!!');
            $sportvatarTemplate->update([
                'name' => $flowMeta['name'],
                'description' => $flowMeta['description'],
                'layer' => $flowMeta['layer'],
                'sport' => $flowMeta['sport'],
                'rarity' => $flowMeta['rarity'],
                'svg' => $flowMeta['svg'],
                'max_mintable' => $flowMeta['maxMintableComponents'],
                'minted' => $flowMeta['totalMintedComponents'],
                'sportvatar_series_id' => $seriesId,
                'flow_id' => $id,
                'flow_address' => config('services.flow.address'),
                'flow_metadata' => $flowMeta
            ]);
        } else {

            info('Sportvatar Template Created - make new template!!!!');
            $sportvatarTemplate = SportvatarTemplate::create(
                [
                    'name' => $flowMeta['name'],
                    'description' => $flowMeta['description'],
                    'layer' => $flowMeta['layer'],
                    'sport' => $flowMeta['sport'],
                    'rarity' => $flowMeta['rarity'],
                    'svg' => $flowMeta['svg'],
                    'max_mintable' => $flowMeta['maxMintableComponents'],
                    'minted' => $flowMeta['totalMintedComponents'],
                    'sportvatar_series_id' => $seriesId,
                    'flow_id' => $id,
                    'flow_address' => config('services.flow.address'),
                    'flow_metadata' => $flowMeta
                ]
            );
        }
        info('Updated Sportvatar Template with Flow data! ' . $sportvatarTemplate->id);
        return $sportvatarTemplate;
    }
    protected function syncSportvatarTemplateFromFlowId($id, $forceUpdate = false)
    {
        $sportvatarTemplate = SportvatarTemplate::whereFlowId($id)->first();
        if($sportvatarTemplate && !$forceUpdate){
            return $sportvatarTemplate;
        } else {
            info('Sportvatar Template Created - Get from chain!!!!');
            $sportvatarF = $this->parseFlowResponse($this->getSportvatarTemplateFromChain($id));
            if($sportvatarF && isset($sportvatarF['id'])){
                info('Sportvatar Template Created - Found!!!!'.$sportvatarF['id']);
                //info(print_r($sportvatarF, true));
                return $this->syncSportvatarTemplateFromFlow($sportvatarF['id'], $sportvatarF);
            }
        }
        return null;
    }



    protected function syncSportvatarPackFromFlow($id, $flowMeta){
        $sportvatarPack = Pack::updateOrCreate(
            ['flow_id' => $id],
            ['flow_metadata' => $flowMeta]
        );
        info('Updated Sportvatar Pack with Flow data! ' . $sportvatarPack->id);
        return $sportvatarPack;
    }


    protected function cleanupSales($id, $address, $type='sportvatar')
    {
        $type = ($type == 'sportvatar') ? 'App\Models\Sportvatar' : 'App\Models\Sportbit';
        SaleOffer::whereFlowId($id)->where('flow_address', '!=', $address)->whereItemType($type)->delete();
    }




    protected function parseFlowFields($items){
        $outArr = [];
        //dd($items);
        foreach($items as $item){

            if(isset($item->value) && isset($item->value->type)){

                $value = $item->value;
                if($value->type == 'Optional') {
                    if(isset($value->value->value)){
                        $value = $value->value;
                    } else {
                        $value = null;
                    }
                }

                if(isset($item->name) && isset($value->value)) {

                    if($value->type == 'Dictionary' && is_array($value->value)){
                        $outArr[$item->name] = [];
                        foreach($value->value as $val){
                            if(isset($val->key) && isset($val->key->value) && isset($val->value) && isset($val->value->value)){
                                if(isset($val->value->value->id) && isset($val->value->value->fields) && $val->value->value->id == $this->getLayersAddress()){
                                    info('PARSING LAYERS!');
                                    $outArr[$item->name][$val->key->value] = $this->parseFlowFields($val->value->value->fields);
                                } else {
                                    $outArr[$item->name][$val->key->value] = $val->value->value;
                                }
                            } else {
                                info('UNSUPPORTED PARSING!!!! PLEASE CHECK 1');
                            }
                        }
                    } else if(isset($value->type) && $value->type == 'Struct' && isset($value->value->fields) && is_array($value->value->fields)) {
                        $outArr[$item->name] = $this->parseFlowFields($value->value->fields);
                    } else {
                        $outArr[$item->name] = $value->value;
                    }
                } else if($value && isset($value->fields) && is_array($value->fields)) {
                    $outArr[] = $this->parseFlowFields($value->fields);
                } else if(isset($item->name)) {
                    $outArr[$item->name] = $value;
                } else {
                    info('UNSUPPORTED PARSING!!!! PLEASE CHECK 3');
                }

            } else {
                info('UNSUPPORTED PARSING!!!! PLEASE CHECK 4');
            }
        }
        return $outArr;
    }


    protected function parseFlowResponse($response){
        if(!$response){
            return null;
        }
        $items = json_decode($response);
        if($items && isset($items->type) && isset($items->value)){
            $value = $items->value;
            if($items->type == 'Optional') {
                if(isset($value->value->value) || (isset($value->value->fields) && is_array($value->value->fields))){
                    $value = $value->value;
                } else {
                    $value = null;
                }
            }

            if(is_array($value)){
                $outArr = [];
                foreach($value as $item){
                    if(isset($item->value) && isset($item->value->fields) && is_array($item->value->fields)){
                        $outArr[] = $this->parseFlowFields($item->value->fields);
                    } else if(isset($item->value)){
                        $outArr[] = $item->value;
                    }
                }
                return $outArr;
            } else if(isset($value->fields) && is_array($value->fields)){
                return $this->parseFlowFields($value->fields);
            } else {
                info(print_r($items, true));
                info('UNSUPPORTED PARSING!!!! PLEASE CHECK');
            }
        } else if($items && isset($items->type) && $items->type == 'Optional' && $items->value == null){
            info('Returned Optional null');
        } else {
            info(print_r($items, true));
            info('UNSUPPORTED PARSING!!!! PLEASE CHECK 2');
        }

        return null;

    }


    protected function parseFlowEvents($response){

        if(!$response){
            return null;
        }
        $items = json_decode($response);

        //dd($items);
        $outArr = [];

        if(is_array($items)){
            foreach($items as $item){
                if(
                    isset($item->blockID) &&
                    isset($item->transactionId) &&
                    isset($item->index) &&
                    isset($item->type) &&
                    isset($item->values) &&
                    isset($item->values->value) &&
                    isset($item->values->value->fields) &&
                    is_array($item->values->value->fields)
                ){
                    $eventArr = [];
                    $eventArr['blockID'] = $item->blockID;
                    $eventArr['transactionId'] = $item->transactionId;
                    $eventArr['index'] = $item->index;
                    $eventArr['type'] = $item->type;
                    $eventArr['values'] = $this->parseFlowFields($item->values->value->fields);
                    $outArr[] = $eventArr;
                }
            }
        } else {
            info('UNSUPPORTED EVENT PARSING!!!! PLEASE CHECK');
        }
        return $outArr;

    }


    protected function updateTemplatesBurnCount($newSportvatar){
        if(isset($newSportvatar->flow_metadata['layers'])){
            foreach ($newSportvatar->flow_metadata['layers'] as $layer) {
                if ($layer && isset($layer['value']) && $layer['value'] > 0) {
                    info($layer['value']);
                    $this->syncSportvatarTemplateFromFlowId($layer['value'], true);
                }
            }
            if(isset($newSportvatar->flow_metadata['series'])){
                $this->syncSeriesFromFlowId($newSportvatar->flow_metadata['series'], true);
            }
            $template = SportvatarTemplate::whereName('Sport Flame')->whereRarity($newSportvatar->rarity)->first();
            if ($template) {
                $this->syncSportvatarTemplateFromFlowId($template->flow_id, true);
            }
        }
    }

    protected function syncSeriesFromFlowId($id, $forceUpdate = false)
    {
        $sportvatarSeries = SportvatarSeries::whereFlowId($id)->first();
        if($sportvatarSeries && !$forceUpdate){
            return $sportvatarSeries;
        } else {
            info('Sportvatar Series Created - Get from chain!!!!');
            $seriesChain = $this->parseFlowResponse($this->getSeriesFromChain($id));
            if($seriesChain && isset($seriesChain['id'])){
                info('Sportvatar Series Created - Found!!!!'.$seriesChain['id']);
                //info(print_r($seriesChain, true));
                return $this->syncSeriesFromFlow($seriesChain['id'], $seriesChain);
            }
        }
        return null;
    }

    protected function getSeriesFromChain($id)
    {
        $results = null;
        try {
            $results = Flow::script('./scripts/get_series.cdc')->argInt($id)->run();
        } catch (ProcessFailedException $e) {
            info('Process failed Collectible Series!!!');
        }

        return $results;
    }

    protected function syncSeriesFromFlow($id, $flowMeta){
        $sportvatarSeries = SportvatarSeries::whereFlowId($id)->first();
        if(!$sportvatarSeries){
            info('Collectible Series Created - get by name!!!!');
            $sportvatarSeries = SportvatarSeries::whereNull('flow_id')->where('name', 'like', $flowMeta['name'])->first();
        } else {
            info('Collectible Series Created - found by ID!!!!');
        }
        if($sportvatarSeries){
            info('Collectible Series Created - found series!!!!');
            $sportvatarSeries->update([
                'name' => $flowMeta['name'],
                'description' => $flowMeta['description'],
                'svg_prefix' => $flowMeta['svgPrefix'],
                'svg_suffix' => $flowMeta['svgSuffix'],
                'layers' => $flowMeta['layers'],
                'colors' => $flowMeta['colors'],
                'max_mintable' => $flowMeta['maxMintable'],
                'minted' => isset($flowMeta['totalMintedCollectibles']) ? $flowMeta['totalMintedCollectibles'] : 0,
                'flow_id' => $id,
                'flow_address' => config('services.flow.address'),
                'flow_metadata' => $flowMeta
            ]);
        } else {
            info('Collectible Series Created - make new series!!!!');
            $sportvatarSeries = SportvatarSeries::create(
                [
                    'name' => $flowMeta['name'],
                    'description' => $flowMeta['description'],
                    'svg_prefix' => $flowMeta['svgPrefix'],
                    'svg_suffix' => $flowMeta['svgSuffix'],
                    'layers' => $flowMeta['layers'],
                    'colors' => $flowMeta['colors'],
                    'max_mintable' => $flowMeta['maxMintable'],
                    'minted' => isset($flowMeta['totalMintedCollectibles']) ? $flowMeta['totalMintedCollectibles'] : 0,
                    'flow_id' => $id,
                    'flow_address' => config('services.flow.address'),
                    'flow_metadata' => $flowMeta
                ]
            );
        }
        info('Updated Sportvatar Series with Flow data! ' . $sportvatarSeries->id);
        return $sportvatarSeries;
    }



    protected function getLatestBlockHeight(){
        $block = Flow::getLatestBlock();
        $items = json_decode($block);
        return ($items && isset($items->height)) ? $items->height : null;
    }


    protected function getEventAddress($type){
        return isset($this->eventsList[$type]) ? 'A.'.str_replace('0x', '', config('services.flow.address')).'.'.$this->eventsList[$type] : null;
    }
    protected function getStorefrontEventAddress($type){
        return isset($this->eventsList[$type]) ? 'A.'.str_replace('0x', '', config('services.flow.storefrontaddress')).'.'.$this->eventsList[$type] : null;
    }

    protected function getSportvatarNFTAddress(){
        return 'A.'.str_replace('0x', '', config('services.flow.address')).'.Sportvatar.NFT';
    }

    /**
     * Currency of an NFTStorefrontV2 listing event from its salePaymentVaultType:
     * 'DUC' (DapperUtilityCoin, Dapper Wallet, shown as USD), 'FLOW' (FlowToken, Flow Wallet) or null (anything else).
     * Matches the exact contract address, so a look-alike "FlowToken" deployed elsewhere is not treated as FLOW.
     */
    protected function getStorefrontCurrency($metadata){
        $typeId = null;
        if(isset($metadata['salePaymentVaultType'])){
            $vaultType = $metadata['salePaymentVaultType'];
            $typeId = is_array($vaultType) ? ($vaultType['typeID'] ?? null) : (is_string($vaultType) ? $vaultType : null);
        }
        if(!$typeId){
            return null;
        }
        $duc = 'A.'.strtolower(str_replace('0x', '', (string) config('services.flow.ducaddress'))).'.DapperUtilityCoin.Vault';
        $flow = 'A.'.strtolower(str_replace('0x', '', (string) config('services.flow.flowtokenaddress'))).'.FlowToken.Vault';
        if($typeId === $duc){
            return 'DUC';
        }
        if($typeId === $flow){
            return 'FLOW';
        }
        return null;
    }
    protected function getComponentNFTAddress(){
        return 'A.'.str_replace('0x', '', config('services.flow.address')).'.Sportbit.NFT';
    }
    protected function getLayersAddress(){
        return 'A.'.str_replace('0x', '', config('services.flow.address')).'.SportvatarTemplate.Layer';
    }

}
