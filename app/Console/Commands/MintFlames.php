<?php

namespace App\Console\Commands;

use App\Jobs\MintComponent as MintComponentJob;
use App\Jobs\MintComponentFake as MintComponentFakeJob;
use App\Models\SportvatarTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MintFlames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:mint-flames {--prod}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mints all the flames!';

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
        $templates = SportvatarTemplate::whereName('Sport Flame')->whereNotNull('flow_id')->get();
        $max_batch = 40;
        $skip_array = [2,3,4,5,6,7,8];

        foreach($templates as $template){
            if(in_array($template->layer, $skip_array)){
                $this->info('Skipping Template '.$template->id.' - '.$template->name);
            } else {
                $count = $template->sportbits()->count();
                $max_mintable = $template->max_mintable;
                if($template->max_mintable == 0){
                    $count = 0;
                    $max_mintable = 4000;
                } else if($template->max_mintable == 10000){
                    $max_mintable = min(1000, $max_mintable - $count);
                    $count = 0;
                } else if($template->max_mintable == 50000){
                    $max_mintable = min(2000, $max_mintable - $count);
                    $count = 0;
                } else if($template->max_mintable == 250000){
                    $max_mintable = min(4000, $max_mintable - $count);
                    $count = 0;
                }
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
