<?php

namespace App\Console\Commands;

use App\Jobs\SyncQueueIt as SyncQueueItJob;
use Illuminate\Console\Command;

class SyncQueueIt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:sync-queueit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs Queue-it configuration file';

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
        $this->info('Syncing Queue it');
        SyncQueueItJob::dispatch();

        return true;
    }
}
