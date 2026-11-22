<?php

namespace App\Jobs;


use App\Models\Pack;
use Carbon\Carbon;
use Flow;

class CleanPack extends BaseJob
{


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {

        $minutes = max(1, (int) config('sportvatar.packReservationMinutes', 60));
        $packs = Pack::readyToSell()->whereNotNull('assigned_at')->whereNotNull('user_id')->where('assigned_at', '<', Carbon::now()->subMinutes($minutes))->get();

        $packsAvailable = $this->parseFlowResponse(Flow::script('./scripts/get_packs.cdc')->argAddress(config('services.flow.address'))->run());

        if($packsAvailable && is_array($packsAvailable)){
            $packs->each(function($pack) use ($packsAvailable){

                // Still in the admin collection on chain = not sold: release the reservation.
                if(in_array((string) $pack->flow_id, array_map('strval', $packsAvailable), true)){
                    info('Resetting pack '.$pack->id);
                    $pack->update([
                        'user_id' => null,
                        'assigned_at' => null
                    ]);
                }

            });
        }

    }

}
