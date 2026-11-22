<?php

namespace App\Console\Commands;

use App\Jobs\SyncEvents as SyncEventsJob;
use Illuminate\Console\Command;

class SyncEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:sync-events {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all the events from the blockchain';

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

        //info('Syncing all events');
        $this->info('Syncing all events');
        $isAll = $this->option('all');
        if($isAll){
            SyncEventsJob::dispatch(true);
        } else {
            SyncEventsJob::dispatch();
        }

        return self::SUCCESS;
    }
}
