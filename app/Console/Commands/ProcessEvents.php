<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGraffleEvents as ProcessEventsJob;
use App\Models\Event;
use Illuminate\Console\Command;

class ProcessEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:process-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all the events synced from the blockchain';

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

        info('Processing all events');
        $this->info('Processing all events');


        Event::whereSynced(false)->whereGraffle(false)->orderBy('block_id', 'asc')->orderBy('index', 'asc')->chunk(1000, function ($events) {
            $events->each(function($event) {
                ProcessEventsJob::dispatch($event, false);
            });
        });


        return self::SUCCESS;
    }
}
