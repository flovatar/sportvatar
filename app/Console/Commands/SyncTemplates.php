<?php

namespace App\Console\Commands;

use App\Jobs\SyncEvents as SyncEventsJob;
use Illuminate\Console\Command;
use App\Models\SportvatarTemplate;
use App\Models\SportvatarProdTemplate;

class SyncTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:sync-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs all the events from the blockchain';

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

        $templates = SportvatarProdTemplate::get();

        foreach($templates as $template){
            $temp = SportvatarTemplate::where('name', $template->name)->where('rarity', $template->rarity)->first();
            if($temp){
                $temp->update([
                    'svg' => $template->svg
                ]);
                $this->info('Syncing template '.$template->name);
            }
        }

        return self::SUCCESS;
    }
}
