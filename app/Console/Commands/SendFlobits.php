<?php

namespace App\Console\Commands;

use App\Jobs\SendFlobit;
use App\Models\Sportbit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SendFlobits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:send-flobits {rarity} {color} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Flobits';

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


        foreach($addressArray as $address){


            $component = Sportbit::whereHas('sportvatarTemplate', function(Builder $query) use ($rarity, $color) {
                if($rarity == 'rare' || $rarity == 'common' || $rarity == 'epic' || $rarity == 'legendary' ) {
                    return $query->whereComponent()->whereColor($color)->whereRarity($rarity);
                } else {
                    return $query->whereComponent()->whereColor($color);
                }
            })->whereFlowAddress(config('services.flow.address'))->whereDestroyed(0)->wherePacked(0)->inRandomOrder()->first();


            if($component){
                info('Sending component '.$component->flow_id.' to '.$address);
                $this->info('Sending component '.$component->flow_id.' to '.$address);
                $component->update([
                    'flow_address' => $address
                ]);
                SendFlobit::dispatch($component, $address);
                $output = $output.$component->flow_id.' - '.$address."\n";
            }
        }

        Storage::disk('localstorage')->put('airdrop/Completed-'.$this->argument('file'), $output);

        info('Finished sending components '.$rarity);
        $this->info('Finished sending components '.$rarity);



        return self::SUCCESS;
    }
}
