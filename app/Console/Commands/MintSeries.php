<?php

namespace App\Console\Commands;

use App\Jobs\MintSeries as MintSeriesJob;
use App\Models\SportvatarSeries;
use App\Models\SportvatarTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MintSeries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:mint-series';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the Sportvatar Series from the DB';

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
        $series = SportvatarSeries::whereNull('flow_id')->get();

        foreach($series as $item){
            info('Creating NFT for Series '.$item->id);
            $this->info('Creating NFT for Series '.$item->id);
            MintSeriesJob::dispatch($item);
        }


        $this->info('All done!');
    }
}
