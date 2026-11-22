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
        $schedule->command('sportvatar:sync-events')->everyMinute();
        $schedule->command('sportvatar:process-events')->everyMinute();
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
