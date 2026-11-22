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
    public function __invoke(Request $request)
    {
        //info('--------------------- RECEIVED GRAFFLE EVENT -------------------');
        //info(print_r($request->all(),true));

        if($request->id && $request->flowEventId && $request->blockHeight && $request->blockEventData && $request->flowTransactionId) {
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
            return abort(404);
        }
    }
}
