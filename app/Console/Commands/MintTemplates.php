<?php

namespace App\Console\Commands;

use App\Jobs\MintTemplate as MintTemplateJob;
use App\Models\SportvatarTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MintTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:mint-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the Templates NFT from all the existing template in the DB';

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
        $templates = SportvatarTemplate::whereNull('flow_id')->get();

        foreach($templates as $template){
            info('Creating NFT for Template '.$template->id);
            $this->info('Creating NFT for Template '.$template->id);
            MintTemplateJob::dispatch($template);
        }


        $this->info('All done!');
    }
}
