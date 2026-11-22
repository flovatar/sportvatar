<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // Built-in indexer (flow:stream daemon + REST catch-up) replaces Graffle and the Flow CLI
        // pull sync (sportvatar:sync-events / process-events, which only ingested Created events).
        $schedule->command('flow:catchup')->everyFiveMinutes()->withoutOverlapping(30)->runInBackground();
        $schedule->command('flow:process --queue')->everyMinute()->withoutOverlapping();
        //$schedule->command('sportvatar:sync-queueit')->everyFiveMinutes();
        $schedule->command('sportvatar:clean-packs')->hourly();
        //$schedule->command('sportvatar:process-graffle')->everyThirtyMinutes();
        //php artisan sportvatar:process-graffle
        //$schedule->command('telescope:prune')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
