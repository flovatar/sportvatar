<?php

namespace App\Jobs;


use App\Models\Sportbit;
use Carbon\Carbon;
use Flow;

class CleanSportbit extends BaseJob
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

        $sportbitAvailable = $this->parseFlowResponse(Flow::script('./scripts/get_sportbit_ids.cdc')->argAddress(config('services.flow.address'))->run());


        if($sportbitAvailable && is_array($sportbitAvailable)){


            info('Loaded Sportbits: '.count($sportbitAvailable));

            $sportbits = Sportbit::wherePacked(1)->get();
            $sportbits->each(function($sportbit) use ($sportbitAvailable) {

                if(in_array($sportbit->flow_id, $sportbitAvailable)){
                    info('Found Sportbit '.$sportbit->flow_id);

                    $sportbit->update([
                        'packed' => 0,
                        'destroyed' => 0
                    ]);

                }

            });
        }

    }

}
