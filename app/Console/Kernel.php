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
        if (config('sportvatar.indexerMode') === 'scheduled') {
            // No Supervisor daemons: poll the chain, process inline and drain the job queues every minute.
            $schedule->command('flow:catchup')->everyMinute()->withoutOverlapping(10)->runInBackground();
            $schedule->command('flow:process')->everyMinute()->withoutOverlapping(10)->runInBackground();
            $schedule->command('queue:work redis --queue=flow-events,default,images --stop-when-empty --max-time=50 --tries=3 --memory=192')
                ->everyMinute()->withoutOverlapping(10)->runInBackground();
        } else {
            $schedule->command('flow:catchup')->everyFiveMinutes()->withoutOverlapping(30)->runInBackground();
            $schedule->command('flow:process --queue')->everyMinute()->withoutOverlapping();
        }
        //$schedule->command('sportvatar:sync-queueit')->everyFiveMinutes();
        $schedule->command('sportvatar:clean-packs')->hourly();
        // Heavy batch jobs (render batches, headless builds) run through bin/heavy-job: ONE server-wide flock shared by
        // the 4 sites on the box (flovatar, sportvatar, both testnets) + nice/ionice, so only one of them runs at a
        // time (mysqld was OOM-killed while overnight builds overlapped). Times are staggered per site (UTC):
        //   hourly  :07 sv-testnet render  :11 fw-testnet shells  :13 sv-testnet shells  :23 fw render
        //           :37 sv render  :41 fw shells  :43 sv shells  :53 fw-testnet render
        //   nightly 02:30 fw-testnet full  03:30 fw full  04:15 sv full  05:00 sv-testnet full
        //   weekly  Sun 05:30 fw render:prune  05:45 sv  06:00 fw-testnet  06:15 sv-testnet
        // Keep the table in sync with flovatar-web's Kernel (same bin/heavy-job) and KB notes/server-flovatar.md.
        $mainnet = config('services.flow.network') === 'mainnet';
        if (config('render.enabled')) {
            // safety net for the event hook: static items without any image (throttled, resumable); animated ones come from the browser renderer
            $this->heavy($schedule, 'render:images all --missing --static-only --limit=60 --sleep=500', 900)
                ->hourlyAt($mainnet ? 37 : 7)->withoutOverlapping(60)->runInBackground();
            // weekly R2 clean-up: render versions superseded > 30 days ago (current versions always kept, see RenderPrune)
            $this->heavy($schedule, 'render:prune --days=30', 1800)
                ->weeklyOn(0, $mainnet ? '05:45' : '06:15')->withoutOverlapping(120)->runInBackground();
        }
        if (config('headless.enabled')) {
            // static headless site (R2): shells hourly (home picks, template lists, stats snapshots), full rebuild nightly as
            // the safety net for the per-item regeneration jobs (HeadlessRegen). Unchanged objects are skipped.
            $this->heavy($schedule, 'headless:build --shells --sitemap --quiet-puts', 900)
                ->hourlyAt($mainnet ? 43 : 13)->withoutOverlapping(30)->runInBackground();
            $this->heavy($schedule, 'headless:build --quiet-puts', 3600)
                ->dailyAt($mainnet ? '04:15' : '05:00')->withoutOverlapping(180)->runInBackground();
        }
        // free-pack vouchers reserved by authorize-claim but never claimed on chain go back to 'issued'
        $schedule->command('sportvatar:release-voucher-reservations')->everyFifteenMinutes()->withoutOverlapping(10)->runInBackground();
        //$schedule->command('sportvatar:process-graffle')->everyThirtyMinutes();
        //php artisan sportvatar:process-graffle
        //$schedule->command('telescope:prune')->daily();
    }

    /**
     * Artisan command wrapped in bin/heavy-job (server-wide lock, waits up to $wait seconds, then skips with exit 75).
     */
    protected function heavy(Schedule $schedule, string $command, int $wait)
    {
        $name = basename(base_path()).' '.$command;

        return $schedule->exec(base_path('bin/heavy-job').' -w '.$wait.' -n '.escapeshellarg($name).' -- '
            .escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('artisan')).' '.$command);
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
