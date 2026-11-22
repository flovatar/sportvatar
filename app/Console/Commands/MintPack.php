<?php

namespace App\Console\Commands;

use App\Jobs\MintPackFileBatch as MintPackJob;
use App\Models\Sportbit;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MintPack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:mint-pack {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new pack';

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
        info('Creating Packs');
        $this->info('Creating Packs');


        $file = Storage::get('randomizer/'.$this->argument('file'));

        $packArr = json_decode($file);

        //$offset = 7999;
        //$packFree = collect($packArr->common)->slice(0,$offset);
        //$packSaleCommon = collect($packArr->common)->slice($offset);

        //$this->mintPackBatch($packFree->toArray(), 0.00, 'Genesis Free Pack', 1,1);
        //$this->mintPackBatch($packSaleCommon->toArray(), 9.99, 'Genesis Standard Pack', 1,1);
        $this->mintPackBatch($packArr->common, 9.99, 'S1 Standard Pack', 1,1);
        $this->mintPackBatch($packArr->rare, 24.99, 'S1 Rare Pack', 2,3);
        $this->mintPackBatch($packArr->epic, 69.99, 'S1 Epic Pack', 3,6);
        $this->mintPackBatch($packArr->legendary, 199.99, 'S1 Legendary Pack', 4, 10);


        $this->info('All done!');
    }


    /*
     *
     *

components: [UInt64],
randomString: [String],
price: [UFix64],
flameCount: UInt32,
series: UInt32,
name: [String],
packCount: UInt32,
compPerPack: UInt32
    ''.floatval($this->price)

    __construct($components, $price, $name, $series = 1, $flameQuantity = 1, $packQuantity = 1, $compPerPack = 1)
     */



    protected function mintPackBatch($packs, $price, $name, $flamesPerPack, $compPerPack){

        $packCount = 10;

        $i = 0;
        $components = [];
        $counter = 0;
        foreach($packs as $pack){
            foreach($pack->flames as $key => $val){
                $components[] = $key;
            }
            foreach($pack->components as $key => $val){
                $components[] = $key;
            }
            foreach($pack->numbers as $key => $val){
                $components[] = $key;
            }


            $i++;
            if($i >= $packCount){

                info('Creating Batch Pack '.$name);
                //info(print_r($components, true));



                MintPackJob::dispatch($components, ''.floatval($price), $name, 1, $flamesPerPack, $i, $compPerPack, $this->getSecret($i));
                $i=0;
                $components = [];
            }
            $counter++;

        }

        if(count($components) > 0 && $i > 0){
            MintPackJob::dispatch($components, ''.floatval($price), $name, 1, $flamesPerPack, $i, $compPerPack, $this->getSecret($i));
        }

    }


    protected function getSecret($count){
        $secret = [];

        for($j = 0; $j < $count; $j++){
            $secret[] = Str::random(32);
        }
        return $secret;
    }

    protected function objToArr($obj){
        $arr = [];
        foreach($obj as $key => $val){
            $arr[] = $key;
        }
        return $arr;
    }
}
