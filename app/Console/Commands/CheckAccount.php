<?php

namespace App\Console\Commands;

use App\Jobs\TestFlow;
use Illuminate\Console\Command;

class CheckAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:check-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the used account';

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

        info('Check Account');
        $this->info('Check Account');
        TestFlow::dispatch();

        return self::SUCCESS;
    }
}
