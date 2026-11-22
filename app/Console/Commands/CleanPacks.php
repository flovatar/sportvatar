<?php

namespace App\Console\Commands;

use App\Jobs\CleanPack;
use Illuminate\Console\Command;
use App\Models\Pack;
use Carbon\Carbon;

class CleanPacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:clean-packs';

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

        info('Check Packs');

        $packs = Pack::readyToSell()->whereNotNull('assigned_at')->whereNotNull('user_id')->where('assigned_at', '<', Carbon::now()->subMinutes(60))->get();

        $this->info('Found '.$packs->count().' Packs');

        CleanPack::dispatch();
        $this->info('All done!');
        return true;

    }
}
