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

        $packs = Pack::readyToSell()->whereNotNull('assigned_at')->whereNotNull('user_id')->where('assigned_at', '<', Carbon::now()->subMinutes(60))->get();

        $packsAvailable = $this->parseFlowResponse(Flow::script('./scripts/get_packs.cdc')->argAddress(config('services.flow.address'))->run());

        if($packsAvailable && is_array($packsAvailable)){
            $packs->each(function($pack) use ($packsAvailable){

                if(in_array($pack->flow_id, $packsAvailable)){
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
