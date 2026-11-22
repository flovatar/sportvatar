<?php

namespace App\Jobs;

use App\Models\Sportbit;
use App\Models\Pack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;
use Illuminate\Support\Str;

class MintPackFile extends BaseJob
{

    protected $components;
    protected $sparkQuantity;
    protected $series;
    protected $name;
    protected $price;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($components, $price, $name = 'Sportvatar Pack', $series = 1, $sparkQuantity = 1)
    {
        $this->components = $components;
        $this->sparkQuantity = $sparkQuantity;
        $this->series = $series;
        $this->name = $name;
        $this->price = $price;
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
        $secret = Str::random(32);

        info('Creating Pack with secret '.$secret);


        Flow::transaction('./transactions/create/pack.cdc')
            ->argArrayInt($this->components)
            ->argString($secret)
            ->argFix(''.floatval($this->price))
            ->argInt32($this->sparkQuantity)
            ->argInt32($this->series)
            ->argString($this->name)
            ->signer(config('sportvatar.signer'))
            ->run();

        info('Created Pack in Flow with secret '.$secret);

        $pack = Pack::create([
            'secret' => $secret,
            'spark_count' => $this->sparkQuantity,
            'series' => $this->series,
            'name' => $this->name
        ]);


        info('Created Pack in DB with secret '.$secret.' and ID '.$pack->id);


        foreach($this->components as $componentId){
            $component = Sportbit::whereFlowId($componentId)->first();
            if($component){
                $component->update(['packed' => 1]);
            }
        }

    }

}
