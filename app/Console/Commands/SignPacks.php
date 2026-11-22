<?php

namespace App\Console\Commands;

use App\Jobs\SignPack;
use App\Models\Pack;
use Illuminate\Console\Command;

class SignPacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:sign-packs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Signs all the packs';

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

        info('Sign Packs');

        $packs = Pack::whereNotNull('secret')->whereNull('signature')->get();

        $this->info('Signing '.$packs->count().' Packs');
        $packs->each(function($pack){
            SignPack::dispatch($pack);
        });

        return true;
    }
}
