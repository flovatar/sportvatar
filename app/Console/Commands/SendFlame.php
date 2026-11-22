<?php

namespace App\Console\Commands;

use App\Jobs\SendFlobits;
use App\Models\Sportbit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SendFlame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:send-flame {address} {quantity}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Flame';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $output = "";

        $address = $this->argument('address');
        $quantity = $this->argument('quantity');

        info('Sending '.$quantity.' Flames to '.$address);
        $this->info('Sending '.$quantity.' Flames to '.$address);



            $components = Sportbit::whereFlame()->whereFlowAddress(config('services.flow.address'))->whereDestroyed(0)->wherePacked(0)->take($quantity)->get();


            if($components){
                info('Sending component '.$components->count().' to '.$address);
                $this->info('Sending component '.$components->count().' to '.$address);
                foreach($components as $component){
                    $component->update([
                        'flow_address' => $address
                    ]);
                    $output = $output.$component->flow_id.' - '.$address."\n";
                }
                SendFlobits::dispatch($components->pluck('flow_id'), $address);
            }


        info('Finished sending Flames');
        $this->info('Finished sending Flames');



        return true;
    }
}
