<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGraffleEvents as ProcessGraffleEventsJob;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessGraffleEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:process-graffle {eventId?} {--all} {--days=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all the Graffle events from the blockchain';

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


        if($this->argument('eventId')){
            $this->info('Syncing Graffle event '.$this->argument('eventId'));
            $event = Event::whereId($this->argument('eventId'))->first();
            if($event){
                ProcessGraffleEventsJob::dispatch($event);
            }
        } else {
            $isAll = $this->option('all');
            if ($isAll) {
                $this->info('Syncing all Graffle events');
                Event::whereSynced(false)->whereGraffle(true)->orderBy('block_id', 'asc')->orderBy('index', 'asc')->chunk(1000, function ($events) {
                    $events->each(function($event) {
                        ProcessGraffleEventsJob::dispatch($event);
                    });
                });
            } else {

                $days = $this->option('days');

                if($days && $days > 0){
                    $this->info('Syncing Graffle events in the last '.$days.' days');
                    Event::whereSynced(false)->whereGraffle(true)->where('created_at', '>', Carbon::now()->subDays($days))->orderBy('block_id', 'asc')->orderBy('index', 'asc')->chunk(1000, function ($events) {
                        $events->each(function($event) {
                            ProcessGraffleEventsJob::dispatch($event);
                        });
                    });
                } else {
                    $this->info('Syncing Graffle events in the last 2 hours');
                    Event::whereSynced(false)->whereGraffle(true)->where('created_at', '>', Carbon::now()->subMinutes(120))->orderBy('block_id', 'asc')->orderBy('index', 'asc')->chunk(1000, function ($events) {
                        $events->each(function($event) {
                            ProcessGraffleEventsJob::dispatch($event);
                        });
                    });
                }
            }
        }



        return self::SUCCESS;
    }
}
