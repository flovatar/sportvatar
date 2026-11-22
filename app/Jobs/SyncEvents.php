<?php

namespace App\Jobs;


use App\Models\Event;
use App\Models\Sync;
use Flow;
use Illuminate\Support\Facades\Log;

class SyncEvents extends BaseJob
{

    protected $all;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($all = false)
    {
        //$this->onConnection('database')->onQueue('single');
        $this->all = $all;
        parent::__construct();
    }

    public function getName(){
        return 'SyncEvents';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('handle syncing events!!!!!');
        $latestHeight = $this->getLatestBlockHeight();
        info(print_r($latestHeight,true));

        $list = ($this->all) ? $this->eventsList : $this->eventsBaseList;
        foreach($list as $key => $val){
            info('syncing events!!!!!'.$key);
            $sync = Sync::whereName($key)->first();
            info($key);

            if(!$sync){
                $sync = Sync::create([
                    'name' => $key,
                    'height' => config('services.flow.minBlockHeight')
                ]);
            }

            while($sync->height < $latestHeight) {

                $maxHeight = min($latestHeight, $sync->height + 149);


                $address = str_replace('0x', '', config('services.flow.address'));
                $results = null;
                info('syncing events!!!!!');
                try {
                $results = Flow::event('A.'.$address.'.'.$val)
                    ->minBlock($sync->height)
                    ->maxBlock($maxHeight)
                    ->run();
                info(print_r($results,true));
                } catch (\Exception $exception) {
                    Log::error($exception);
                    $results = null;
                    info(print_r($exception,true));
                }
                if(!$results) {
                    try {
                        $results = Flow::event('A.' . $address . '.' . $val)
                            ->minBlock($sync->height+1)
                            ->maxBlock($maxHeight)
                            ->run();
                        info(print_r($results,true));
                    } catch (\Exception $exception) {
                        Log::error($exception);
                        $results = Flow::event('A.' . $address . '.' . $val)
                            ->minBlock($latestHeight)
                            ->maxBlock($latestHeight)
                            ->run();
                        info(print_r($exception,true));
                    }
                }
                if($results){
                    $this->saveFlowEvents($results);

                    $sync->height = $maxHeight;
                    $sync->save();
                }
                sleep(1);

            }
        }


        //ProcessEvents::dispatch();
    }


    protected function saveFlowEvents($results){

        $flowMetaArr = $this->parseFlowEvents($results);

        foreach ($flowMetaArr as $flowMeta) {

            Event::firstOrCreate(
                [
                    'block_id' => $flowMeta['blockID'],
                    'transaction_id' => $flowMeta['transactionId'],
                    'type' => $flowMeta['type'],
                    'index' => $flowMeta['index'],
                ],
                [
                    'flow_metadata' => $flowMeta['values']
                ]
            );
        }
    }

}
