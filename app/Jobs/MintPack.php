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

class MintPack extends BaseJob
{

    protected $quantity;
    protected $flameQuantity;
    protected $series;
    protected $name;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quantity = 1, $flameQuantity = 1, $series = 1, $name = 'Sportvatar Pack')
    {
        $this->quantity = $quantity;
        $this->flameQuantity = $flameQuantity;
        $this->series = $series;
        $this->name = $name;
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
        for($i=0; $i < $this->quantity; $i++){
            $this->mintPack();
        }
    }

    protected function mintPack()
    {
        $flames = Sportbit::readyForPack()->whereFlame()->take($this->flameQuantity)->get();
        $totalComponents = 5;
        $components = Sportbit::readyForPack()->whereNotCategory('flame')->inRandomOrder()->take($totalComponents)->get();



        if($totalComponents != $components->count() || $this->flameQuantity != $flames->count()){
            info('Skipping Pack mint because some component is missing');
            info(print_r($flames,true));
            info(print_r($components,true));
            return false;
        } else {

            $argInt = array_merge($flames->pluck('flow_id')->toArray(), $components->pluck('flow_id')->toArray());


            $secret = Str::random(32);

            info('Creating Pack with secret '.$secret);



            Flow::transaction('./transactions/create/pack.cdc')
                ->argArrayInt($argInt)
                ->argString($secret)
                ->argFix(''.(floatval(config('sportvatar.price')) * $this->flameQuantity))
                ->argInt32($this->flameQuantity)
                ->argInt32($this->series)
                ->argString($this->name)
                ->signer(config('sportvatar.signer'))
                ->run();

            info('Created Pack in Flow with secret '.$secret);

            $pack = Pack::create([
                'secret' => $secret,
                'flame_count' => $this->flameQuantity,
                'series' => $this->series,
                'name' => $this->name
            ]);


            info('Created Pack in DB with secret '.$secret.' and ID '.$pack->id);


            $flames->each(function($flame) {
                $flame->update(['packed' => 1]);
            });
            $components->each(function($component) {
                $component->update(['packed' => 1]);
            });
        }

    }
}
