<?php

namespace App\Console\Commands;

use App\Jobs\SendPack;
use App\Models\Pack;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CreateNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:create-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create SVG Number Modifiers';

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


        for($i=0; $i<10; $i++){
            for($j=0; $j<10; $j++){

                $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3000 3000">
  <g id="number-'.$i.$j.'" class="number-mod">

    <defs>
      <style>
      
        .football-goalkeeper .number1,
        .football-goalkeeper .number2,
        #Football-Goalkeeper-Green .number1,
        #Football-Goalkeeper-Green .number2,
        #Football-Goalkeeper-Yellow .number1,
        #Football-Goalkeeper-Yellow .number2 {
                 transform: translate(40px, 0) !important;
        }
        
        .alpine-skiing .bib-number,
        #Alpine-Skiing-Black-and-Yellow .bib-number,
         #Alpine-Skiing-Red-and-Blue-Stripes .bib-number,
        #Alpine-Skiing-Gruyere .bib-number {
                 transform: translate(0, 0) !important;
        }
        
        .numbers-shorts .number1,
        .numbers-shorts .number2 {
                 transform: translate(15px, 0) !important;
        }
        
        #Football-Stripes-White-and-Red .number1,
        #Football-Stripes-White-and-Red .number2 {
                 fill: white !important;
        }

        .number {
          opacity: 0 !important;
        }

        .number1 .number._'.$i.',
        .number2 .number._'.$j.' {
          opacity: 1 !important;
          transform: translate(0, 0) !important;
        }


        .af-jersey .number1  .number._'.$i.',
        .af-jersey .number2  .number._'.$j.',
        .volleyball .numbers-shorts .number1 .number._'.$i.',
        .volleyball .numbers-shorts .number2 .number._'.$j.',
        .numbers-arm .number._'.$i.',
        .numbers-arm .number._'.$j.',
        .football-shirt .number._'.$i.',
        .football-shirt .number._'.$j.',
        .basketball-shirt .number._'.$i.',
        .basketball-shirt .number._'.$j.',
        .baseball-shirt .number._'.$i.',
        .baseball-shirt .number._'.$j.' {
          transform: translate(0, 0) !important;
        }

      </style>
    </defs>


  </g>
</svg>';

                Storage::disk('localstorage')->put('v-numbers/Number-'.$i.$j.'_250.svg', $svg);
            }
        }

        $this->info('Finished creating numbers');



        return self::SUCCESS;
    }
}
