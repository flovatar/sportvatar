<?php

namespace App\Console\Commands;

use App\Jobs\SyncSportvatar;
use App\Models\Sportvatar;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RefreshSportvatarFromChain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:refresh-sportvatar-blockchain {flow_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read all the Sportvatars from the blockchain!';

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
        if($this->argument('flow_id')){
            $sportvatar = Sportvatar::whereFlowId($this->argument('flow_id'))->firstOrFail();
            $this->info('Syncing Sportvatar '.$this->argument('flow_id'));
            SyncSportvatar::dispatch($sportvatar->flow_id, $sportvatar->flow_address);
        } else {
            $sportvatars = Sportvatar::select('flow_id', 'flow_address')->whereNotNull('flow_id')->get();

            foreach($sportvatars as $sportvatar){
                $this->info('Syncing Sportvatar '.$sportvatar->flow_id);
                SyncSportvatar::dispatch($sportvatar->flow_id, $sportvatar->flow_address);
            }

            $this->info('Synced '.count($sportvatars).' Sportvatars');
        }

        $this->info('All done!');
    }
}
