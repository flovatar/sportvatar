<?php

namespace App\Console\Commands;

use App\Jobs\CleanSportbit as CleanSportbitJob;
use Illuminate\Console\Command;
use App\Models\Sportbit;
use Carbon\Carbon;
use Flow;

class CleanSportbit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:clean-sportbit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans all the packs';

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

        $this->info('Refreshing Sportbits');


        CleanSportbitJob::dispatch();

        return true;
    }
}
