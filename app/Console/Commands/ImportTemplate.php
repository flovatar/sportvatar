<?php

namespace App\Console\Commands;

use App\Models\SportvatarTemplate;
use App\Models\SportvatarSeries;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Str;

class ImportTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:import-template {folder} {--replace} {--replacesportvatar} {--scaffold} {--internal} {--v2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the Template NFT from an existing folder of SVG files';

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
        $files = Storage::disk('localstorage')->allFiles($this->argument('folder'));

        $i = SportvatarTemplate::count() + 1;
        foreach($files as $file){
            if(!Str::contains($file, ['.DS_Store'])){
                $name = str_replace($this->argument('folder').'/','', $file);
                $folderArr = explode('/', $name);
                if(count($folderArr) == 2){
                    $fullName = str_replace('.svg', '', $folderArr[1]);
                    $fullNameArr = explode('_',$fullName);
                    $name = str_replace('-', ' ', $fullNameArr[0]);
                    $max_mintable = isset($fullNameArr[1]) ? intval($fullNameArr[1]) : 1000;
                    $sport = isset($fullNameArr[2]) ? $fullNameArr[2] : 'general';
                    $name = !isset($fullNameArr[1]) ? str_replace('1000', '', $name) : $name;
                    $rarity = 'common';

                    if($max_mintable == 0){
                        $rarity = 'common';
                    } else if($max_mintable <= 10){
                        $rarity = 'legendary';
                    } else if($max_mintable <= 50){
                        $rarity = 'epic';
                    } else if($max_mintable <= 250){
                        $rarity = 'rare';
                    }

                    if($name == 'Sport Flame'){
                        if($max_mintable == 0){
                            $rarity = 'common';
                        } else if($max_mintable <= 10000){
                            $rarity = 'legendary';
                        } else if($max_mintable <= 50000){
                            $rarity = 'epic';
                        } else if($max_mintable <= 250000){
                            $rarity = 'rare';
                        }
                    }


                    $layerArr = explode('-', $folderArr[0]);
                    $layer = $layerArr[0];

                    $fileContent = Storage::disk('localstorage')->get($file);

                    $fileContent = str_replace('</svg>', '', $fileContent);
                    $fileContent = preg_replace('/<svg[^>]*>/', '', $fileContent);
                    $fileContent = preg_replace('/<\?xml[^>]*>/', '', $fileContent);
                    $fileContent = preg_replace('/<!DOCTYPE[^>]*>/', '', $fileContent);
                    $fileContent = preg_replace('/^<rect[^>]*height="1600"[^>]*>/', '', $fileContent);

                    $fileContent = str_replace('  ', ' ', $fileContent);
                    $fileContent = str_replace("\n", '', $fileContent);
                    $fileContent = str_replace('x-link:href', 'href', $fileContent);


                    $arrReplaceSequence = [
                        'cls-',
                        'linear-gradient-',
                        'radial-gradient-',
                        'Gradient_',
                        'Scene-',
                        'filter',
                        'Layer'
                    ];

                    $arrReplaceId = [
                        'linear-gradient',
                        'radial-gradient',
                        'body',
                        'body-shade',
                        'body_ground_shadwo',
                        'body_ground_shadow',
                        'outline',
                        'ear',
                        'Shadow',
                        'BallShadow',
                        'sheen',
                        'glow',
                        'brain',
                        'brain-shade',
                        'brain-outline',
                        'shine',
                        'colours',
                        'cyborg-suit',
                        'GLOW_BOD_copy',
                        'body-shade',
                        'gashes',
                        'eye-edge',
                        'eyelid-left',
                        'eyelid-right',
                        'eye-shadow',
                        'eye-shadow-right',
                        'eye-left',
                        'eye-right',
                        'laser-left',
                        'laser-right',
                        'pupil',
                        'pupil-closed',
                        'pupil-glow',
                        'ooze',
                        'ooze-drip',
                        'dribble',
                        'goblin-body',
                        'goblin-shade',
                        'ear-shade',
                        'goblin-outline',
                        'glass-top',
                        'brain-shade',
                        'brain-outline',
                        'spots',
                    ];

                    $arrReplaceSportvatar = [
                        'clip-path',
                        'clip-path-2',
                        'clip-path-3',
                        'clip-path-4',
                        'clip-path-5',
                        'clip-path-6',
                        'clip-path-7',
                        'linear-gradient',
                        'linear-gradient-2',
                        'linear-gradient-3',
                        'linear-gradient-4',
                        'linear-gradient-5',
                        'linear-gradient-6',
                        'linear-gradient-7',
                        'radial-gradient',
                        'radial-gradient-2',
                        'radial-gradient-3',
                        'radial-gradient-3',
                        'radial-gradient-4',
                        'radial-gradient-5',
                        'radial-gradient-6',
                        'radial-gradient-7',
                    ];

                    if($this->option('replace')){

                        foreach($arrReplaceSequence as $replaceId){
                            $fileContent = $this->replaceStringSequence($replaceId, $i, $fileContent);
                        }
                        foreach($arrReplaceId as $replaceId){
                            $fileContent = $this->replaceStringId($replaceId, $i, $fileContent);
                        }

                    }


                    if($this->option('replacesportvatar')){
                        foreach($arrReplaceSportvatar as $replaceId){
                            $fileContent = $this->replaceStringIdSportvatar($replaceId, $i, $fileContent);
                        }

                    }




                    $this->info('Importing '.$layer.' - '.$name);

                    if($this->option('internal')){
                        $rarity = 'legendary';
                    }

                    $color = $this->option('internal') ? 'internal' : 'default';
                    $color = $this->option('v2') ? 'v2' : $color;

                    $template = SportvatarTemplate::updateOrCreate([
                        'name' => $name,
                        'layer' => $layer,
                        'rarity' => $rarity,
                        'sport' => $sport,
                        'sportvatar_series_id' => SportvatarSeries::orderBy('flow_id', 'desc')->firstOrFail()->flow_id,
                        'max_mintable' => $max_mintable
                    ],
                        [
                            'svg' => $fileContent
                        ]
                    );

                    if($this->option('scaffold')){
                        $template->update([
                            'flow_id' => $template->id+10000,
                            'flow_address' => config('sportvatar.contractAddress')
                        ]);
                    }
                    $i++;
                }

            }
        }

        $this->info('All done!');
    }

    protected function replaceStringSequence($search, $i, $text){
        for($j = 100; $j >= 0; $j--) {
            $text = str_replace($search.$j, $search.$j.'-'.$i, $text);
        }
        return $text;
    }

    protected function replaceStringId($search, $i, $text){
        $text = str_replace('"'.$search.'"', '"'.$search.'-'.$i.'"', $text);
        $text = str_replace('"#'.$search.'"', '"#'.$search.'-'.$i.'"', $text);
        $text = str_replace('(#'.$search.')', '(#'.$search.'-'.$i.')', $text);
        return $text;
    }

    protected function replaceStringIdSportvatar($search, $i, $text){
        $text = str_replace('"'.$search.'"', '"'.$i.'-'.$search.'"', $text);
        $text = str_replace('"#'.$search.'"', '"#'.$i.'-'.$search.'"', $text);
        $text = str_replace('(#'.$search.')', '(#'.$i.'-'.$search.')', $text);
        return $text;
    }
    protected function replaceString($search, $i, $text){
        return str_replace($search, $search.'-'.$i, $text);
    }
}
