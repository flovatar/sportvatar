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

class SendFlobitsBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:send-flobits-batch {rarity} {color} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Flobits in batch';

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

        $rarity = $this->argument('rarity');
        $color = $this->argument('color');

        info('Sending '.$rarity);
        $this->info('Sending '.$rarity);

        $file = Storage::disk('localstorage')->get('airdrop/'.$this->argument('file'));

        info('Reading file '.$this->argument('file'));
        $this->info('Reading file '.$this->argument('file'));

        $addressArray = explode("\n", $file);


        foreach($addressArray as $line){


            $lineArray = explode(",", $line);
            $address = $lineArray[0];
            $quantity = $lineArray[1];

            $components = Sportbit::whereHas('sportvatarTemplate', function(Builder $query) use ($rarity, $color) {
                $query->whereComponent();
                if($color != 'all'){
                    $query->whereColor($color);
                }
                if($rarity == 'rare' || $rarity == 'common' || $rarity == 'epic' || $rarity == 'legendary' ) {
                    $query->whereRarity($rarity);
                }
                return $query;
            })->whereFlowAddress(config('services.flow.address'))->whereDestroyed(0)->wherePacked(0)->inRandomOrder()->take($quantity)->get();


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
        }

        Storage::disk('localstorage')->put('airdrop/Completed-'.$this->argument('file'), $output);

        info('Finished sending components '.$rarity);
        $this->info('Finished sending components '.$rarity);



        return self::SUCCESS;
    }
}
