<?php

namespace App\Console\Commands;

use App\Jobs\MintComponent as MintComponentJob;
use App\Jobs\MintComponentFake as MintComponentFakeJob;
use App\Models\SportvatarTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MintComponents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:mint-components {--prod}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mints all the components!';

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
        $templates = SportvatarTemplate::where('name', '!=', 'Sport Flame')->whereNotNull('flow_id')->get();
        $max_batch = 40;
        $skip_array = [2,3,4,5,6,7,8];

        foreach($templates as $template){
            if(in_array($template->layer, $skip_array)){
                $this->info('Skipping Template '.$template->id.' - '.$template->name);
            } else {
                $count = $template->sportbits()->count();
                $max_mintable = $template->max_mintable;

                if($count < $max_mintable) {
                    $this->info('Creating Components for Template ' . $template->id . ' - ' . $template->name);
                    while ($count < $max_mintable) {
                        $quantity = min($max_batch, ($max_mintable - $count));
                        $isProd = $this->option('prod');
                        if ($isProd) {
                            MintComponentJob::dispatch($template, $quantity);
                        } else {
                            MintComponentFakeJob::dispatch($template, $quantity, $count);
                        }
                        $count += $quantity;
                    }
                }
            }
        }

        $this->info('All done!');
    }
}
