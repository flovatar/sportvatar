<?php

namespace App\Console\Commands;

use App\Jobs\ExportAllImages;
use App\Models\Sportvatar;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RefreshSportvatarImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:refresh-sportvatar-images {flow_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates all the Sportvatar images from scratch!';

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
        if($this->argument('flow_id')){
            $this->info('Regenerating image for Sportvatar '.$this->argument('flow_id'));
            ExportAllImages::dispatch($this->argument('flow_id'));
        } else {
            $sportvatars = Sportvatar::select('flow_id')->whereNotNull('flow_id')->get();

            foreach($sportvatars as $sportvatar){
                $this->info('Regenerating image for Sportvatar '.$sportvatar->flow_id);
                ExportAllImages::dispatch($sportvatar->flow_id);
            }

            $this->info('Regenerated '.count($sportvatars).' Sportvatar Images');
        }

        $this->info('All done!');
    }
}
