<?php

namespace App\Console\Commands;

use App\Models\SportvatarSeries;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Str;

class ImportSeries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:import-series {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the Sportvatar Series from a JSON file';

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
        $file = Storage::disk('localstorage')->get($this->argument('file'));
        //$this->info($file);
        $seriesJson = json_decode($file);
        //$this->info(print_r($seriesJson, true));

        if(isset($seriesJson->name)){
            SportvatarSeries::updateOrCreate([
                'name' => $seriesJson->name
                ],[
                'svg_prefix' => $seriesJson->svgPrefix,
                'svg_suffix' => $seriesJson->svgSuffix,
                'layers' => $seriesJson->layers,
                'colors' => $seriesJson->colors,
                'max_mintable' => $seriesJson->maxMintable,
                'flow_id' => null,
                'flow_address' => null
            ]);
            $this->info('Series '.$seriesJson->name.' created!');
        }

        $this->info('All done!');
    }

}
