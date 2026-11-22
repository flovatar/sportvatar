<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEvents;
use App\Jobs\ProcessGraffleEvents;
use App\Models\Event;
use App\Models\Sportvatar;
use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GraffleController extends Controller
{
    // NFTStorefrontV2 events processed by ProcessGraffleEvents.
    const STOREFRONT_EVENTS = ['ListingAvailable', 'ListingCompleted'];

    public function __invoke(Request $request)
    {
        //info('--------------------- RECEIVED GRAFFLE EVENT -------------------');
        //info(print_r($request->all(),true));

        if(!$this->hasValidSecret($request)){
            return abort(403);
        }

        if($this->isValidPayload($request)) {
            $eventDate = Carbon::parse($request->createdAt);
            $event = Event::create(
                [
                    'block_id' => $request->blockHeight,
                    'transaction_id' => $request->flowTransactionId,
                    'type' => $request->flowEventId,
                    'index' => ($eventDate->second*10000000) + $eventDate->micro,
                    'graffle' => 1,
                    'event_date' => $eventDate,
                    'flow_metadata' => $request->blockEventData
                ]
            );

            ProcessGraffleEvents::dispatch($event);

            return 'ok';
        } else {
            return abort(422);
        }
    }

    /**
     * Shared secret configured in the Graffle webhook: header X-Graffle-Secret / X-Webhook-Secret,
     * Authorization: Bearer <secret>, or ?secret=<secret> in the webhook URL.
     */
    protected function hasValidSecret(Request $request)
    {
        $secret = (string) config('services.graffle.secret');

        if($secret === ''){
            // Unauthenticated webhooks are never accepted in production.
            return !app()->environment('production');
        }

        $candidates = [
            $request->header('X-Graffle-Secret'),
            $request->header('X-Webhook-Secret'),
            $request->bearerToken(),
            $request->query('secret'),
        ];

        foreach($candidates as $candidate){
            if(is_string($candidate) && $candidate !== '' && hash_equals($secret, $candidate)){
                return true;
            }
        }

        return false;
    }

    protected function isValidPayload(Request $request)
    {
        if(!($request->id && $request->flowEventId && $request->blockHeight && $request->blockEventData && $request->flowTransactionId)){
            return false;
        }

        if(!is_string($request->flowEventId) || !in_array($request->flowEventId, $this->allowedEventTypes(), true)){
            return false;
        }

        if(!preg_match('/^[0-9]{1,12}$/', (string) $request->blockHeight)){
            return false;
        }

        if(!is_string($request->flowTransactionId) || !preg_match('/^(0x)?[0-9a-f]{64}$/', $request->flowTransactionId)){
            return false;
        }

        if(!is_array($request->blockEventData)){
            return false;
        }

        if($request->createdAt !== null){
            try {
                Carbon::parse($request->createdAt);
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Exact event types the app processes: Sportvatar contracts on FLOW_MARKET_ADDRESS
     * and NFTStorefrontV2 listing events on MIX_CONTRACT_STOREFRONT2.
     */
    protected function allowedEventTypes()
    {
        $types = [];

        $market = strtolower(str_replace('0x', '', (string) config('services.flow.address')));
        if(preg_match('/^[0-9a-f]{16}$/', $market)){
            foreach($this->processedEvents() as $event){
                $types[] = 'A.'.$market.'.'.$event;
            }
        }

        $storefront = strtolower(str_replace('0x', '', (string) config('services.flow.storefrontaddress')));
        if(preg_match('/^[0-9a-f]{16}$/', $storefront)){
            foreach(self::STOREFRONT_EVENTS as $event){
                $types[] = 'A.'.$storefront.'.NFTStorefrontV2.'.$event;
            }
        }

        return $types;
    }

    /**
     * Contract.Event names handled by ProcessGraffleEvents (keep in sync with BaseJob::$eventsList).
     */
    protected function processedEvents()
    {
        return [
            'SportvatarTemplate.CreatedSeries', 'SportvatarTemplate.Created',
            'Sportbit.Created', 'Sportbit.Withdraw', 'Sportbit.Deposit', 'Sportbit.Destroyed',
            'SportvatarPack.Created', 'SportvatarPack.Deposit', 'SportvatarPack.Opened',
            'Sportvatar.Created', 'Sportvatar.Withdraw', 'Sportvatar.Deposit', 'Sportvatar.Updated',
        ];
    }
}
