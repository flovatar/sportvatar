<?php

namespace App\Console\Commands;

use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Str;

class RandomizePacksSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:randomize-packs-sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a JSON with randomized pack distribution for the sale';


    protected $packCommonCount = 0; //5000;
    protected $packRareCount = 180; //2000;
    protected $packEpicCount = 810; //1000;
    protected $packLegendaryCount = 500;

    protected $packCount = 1490; //8500;

    // Legendary: 1 Legendary Flame + 3 Random Flame + 6 Sportbits + 4 Numbers => 80 Flames +  120 Sportbits + 2000 Numbers
    // Epic: 1 Epic Flame + 2 Random Flame + 4 Sportbits + 2 Numbers => 300 Flames + 400 Sportbits + 2000 Numbers
    // Rare: 1 Rare Flame + 1 Random Flame + 2 Sportbits + 1Number => 1000 Flames + 1000 Sportbits + 2000 Numbers
    // Standard: 1 Random Flame + 1 Sportbit => 8000 Flames + 8000 Sportbits

    // Total 1050 Flames + 16000 Sportbits + 6000 Numbers



    protected $packComponentCommonTotal = 7000; //11200;
    protected $packComponentRareTotal = 2969; //4300;
    protected $packComponentEpicTotal = 224; //450;
    protected $packComponentLegendaryTotal = 18; //50;

    protected $packNumberRareTotal = 2000;
    protected $packNumberEpicTotal = 2000;
    protected $packNumberLegendaryTotal = 2000;

    protected $packFlameCommonTotal = 2885; //7000;
    protected $packFlameRareTotal = 824; //4000;
    protected $packFlameEpicTotal = 1123; //2000;
    protected $packFlameLegendaryTotal = 678; //1000;

    protected $packComponentCommonCount = 1;
    protected $packComponentRareCount = 2;
    protected $packComponentEpicCount = 4;
    protected $packComponentLegendaryCount = 6;
    protected $packFlameCommonCount = 1;
    protected $packFlameRareCount = 2;
    protected $packFlameEpicCount = 3;
    protected $packFlameLegendaryCount = 4;
    protected $packNumberCommonCount = 0;
    protected $packNumberRareCount = 1;
    protected $packNumberEpicCount = 2;
    protected $packNumberLegendaryCount = 4;



    protected $packComponentTotal;
    protected $packFlameTotal;
    protected $packNumberTotal;


    protected $componentsCommon;
    protected $componentsRare;
    protected $componentsEpic;
    protected $componentsLegendary;
    protected $componentsAllCount;

    protected $componentsRandom;

    protected $flamesCommmon;
    protected $flamesRare;
    protected $flamesEpic;
    protected $flamesLegendary;
    protected $flamesAllCount;

    protected $flamesRandom;


    protected $numbersRare;
    protected $numbersEpic;
    protected $numbersLegendary;
    protected $numbersAllCount;

    protected $numbersRandom;

    protected $randArr = [];



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->packComponentTotal = ($this->packComponentCommonCount * $this->packCommonCount) +
            ($this->packComponentRareCount * $this->packRareCount) +
            ($this->packComponentEpicCount * $this->packEpicCount) +
            ($this->packComponentLegendaryCount * $this->packLegendaryCount);


        $this->packFlameTotal = ($this->packFlameCommonCount * $this->packCommonCount) +
            ($this->packFlameRareCount * $this->packRareCount) +
            ($this->packFlameEpicCount * $this->packEpicCount) +
            ($this->packFlameLegendaryCount * $this->packLegendaryCount);


        $this->packNumberTotal = ($this->packNumberCommonCount * $this->packCommonCount) +
            ($this->packNumberRareCount * $this->packRareCount) +
            ($this->packNumberEpicCount * $this->packEpicCount) +
            ($this->packNumberLegendaryCount * $this->packLegendaryCount);

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->reloadFromDb();

        $this->preCheck();


        if (!$this->confirm('Continue with the randomization?', true)) {
            return 0;
        }


        $finalCheck = false;


        while(!$finalCheck){
            $this->info('Randomizing array');
            $this->randomizeArray(true);
            $this->info('Intermediate check');
            $finalCheck = $this->finalCheck(true);
        }


        $fileName = 'app/randomizer/pack_sportvatar_randomized_'.Carbon::now()->format('Y-m-d-H-i-s').'.json';
        Storage::disk('localstorage')->put($fileName, collect($this->randArr)->toJson());
        $this->info('All done! Randomization completed and stored in file ' . $fileName);

        $this->info('Final check for correct distribution');
        $this->finalCheck(true);

        $this->info('Process completed succesfully!');
    }


    protected function reloadFromDb(){

        $this->flamesCommon = Sportbit::readyForPack()->whereFlameRarity('common')->inRandomOrder()->get()->splice(0,$this->packFlameCommonTotal);
        $this->flamesRare = Sportbit::readyForPack()->whereFlameRarity('rare')->inRandomOrder()->get()->splice(0,$this->packFlameRareTotal);
        $this->flamesEpic = Sportbit::readyForPack()->whereFlameRarity('epic')->inRandomOrder()->get()->splice(0,$this->packFlameEpicTotal);
        $this->flamesLegendary = Sportbit::readyForPack()->whereFlameRarity('legendary')->inRandomOrder()->get()->splice(0,$this->packFlameLegendaryTotal);


        $this->flamesAllCount = $this->flamesCommon->count() + $this->flamesRare->count() + $this->flamesEpic->count() + $this->flamesLegendary->count();

        $this->flamesRandom = collect([]);
        $this->componentsRandom = collect([]);
        $this->numbersRandom = collect([]);

        $this->componentsCommon = Sportbit::readyForPack()->whereRarity('common')->inRandomOrder()->get()->splice(0,$this->packComponentCommonTotal);
        $this->componentsRare = Sportbit::readyForPack()->whereRarity('rare')->inRandomOrder()->get()->splice(0,$this->packComponentRareTotal);
        $this->componentsEpic = Sportbit::readyForPack()->whereRarity('epic')->inRandomOrder()->get()->splice(0,$this->packComponentEpicTotal);
        $this->componentsLegendary = Sportbit::readyForPack()->whereRarity('legendary')->inRandomOrder()->get()->splice(0,$this->packComponentLegendaryTotal);

        $this->componentsAllCount = $this->componentsCommon->count() + $this->componentsRare->count() + $this->componentsEpic->count() + $this->componentsLegendary->count();



        $this->componentsRandom = $this->componentsLegendary->concat($this->componentsEpic)->concat($this->componentsRare)->concat($this->componentsCommon);

        $shuffleAmount = random_int(100,1000);
        for($i=0; $i < $shuffleAmount; $i++){
            $this->componentsRandom = $this->componentsRandom->shuffle();
        }


        //$this->numbersRare = Sportbit::readyForPack()->whereNumber()->inRandomOrder()->get()->splice(0,$this->packNumberRareTotal);
        //$this->numbersEpic = Sportbit::readyForPack()->whereNumber()->inRandomOrder()->get()->splice(0,$this->packNumberEpicTotal);
        //$this->numbersLegendary = Sportbit::readyForPack()->whereNumber()->inRandomOrder()->get()->splice(0,$this->packNumberLegendaryTotal);

        //$this->numbersAllCount = $this->numbersRare->count() + $this->numbersEpic->count() + $this->numbersLegendary->count();
        //$this->numbersRandom = $this->numbersLegendary->concat($this->numbersEpic)->concat($this->numbersRare);
        $this->numbersAllCount = ($this->packNumberRareTotal + $this->packNumberEpicTotal + $this->packNumberLegendaryTotal);
        $this->numbersRandom = Sportbit::readyForPack()->whereNumber()->inRandomOrder()->get()->splice(0,$this->numbersAllCount);


        $shuffleAmount = random_int(100,1000);
        for($i=0; $i < $shuffleAmount; $i++){
            $this->numbersRandom = $this->numbersRandom->shuffle();
        }
    }



    protected function preCheck(){
        $this->info('Pack Standard Count: '.$this->packCommonCount);
        $this->info('Pack Rare Count: '.$this->packRareCount);
        $this->info('Pack Epic Count: '.$this->packEpicCount);
        $this->info('Pack Legendary Count: '.$this->packLegendaryCount);

        $this->info('Components Common Available: '.$this->componentsCommon->count());
        $this->info('Components Rare Available: '.$this->componentsRare->count());
        $this->info('Components Epic Available: '.$this->componentsEpic->count());
        $this->info('Components Legendary Available: '.$this->componentsLegendary->count());
        $this->info('Components Required: '.$this->packComponentTotal);
        $this->info('Components Available: '.$this->componentsAllCount);

        if($this->componentsAllCount < $this->packComponentTotal){
            $this->error('Not enough Components available!');
        }
        if($this->componentsCommon->count() < $this->packComponentCommonTotal){
            $this->error('Not enough Common Components available!');
        }
        if($this->componentsRare->count() < $this->packComponentRareTotal){
            $this->error('Not enough Rare Components available!');
        }
        if($this->componentsEpic->count() < $this->packComponentEpicTotal){
            $this->error('Not enough Epic Components available!');
        }
        if($this->componentsLegendary->count() < $this->packComponentLegendaryTotal){
            $this->error('Not enough Legendary Components available!');
        }



        $this->info('Flames Common Available: '.$this->flamesCommon->count());
        $this->info('Flames Rare Available: '.$this->flamesRare->count());
        $this->info('Flames Epic Available: '.$this->flamesEpic->count());
        $this->info('Flames Legendary Available: '.$this->flamesLegendary->count());
        $this->info('Flames Required: '.$this->packFlameTotal);
        $this->info('Flames Available: '.$this->flamesAllCount);

        if($this->flamesAllCount < $this->packFlameTotal){
            $this->error('Not enough Flames available!');
        }
        if($this->flamesCommon->count() < $this->packFlameCommonTotal){
            $this->error('Not enough Common Flames available!');
        }
        if($this->flamesRare->count() < $this->packFlameRareTotal){
            $this->error('Not enough Rare Flames available!');
        }
        if($this->flamesEpic->count() < $this->packFlameEpicTotal){
            $this->error('Not enough Epic Flames available!');
        }
        if($this->flamesLegendary->count() < $this->packFlameLegendaryTotal){
            $this->error('Not enough Legendary Flames available!');
        }
    }



    protected function randomizePacks(){
        $legendaryPacks = collect($this->randArr['legendary']);
        $epicPacks = collect($this->randArr['epic']);
        $rarePacks = collect($this->randArr['rare']);
        $standardPacks = collect($this->randArr['common']);

        // Using cryptographically secure pseudo-random generator
        // https://www.php.net/manual/en/function.random-int.php
        $shuffleAmount = random_int(100,1000);

        for($i=0; $i < $shuffleAmount; $i++){
            $legendaryPacks = $legendaryPacks->shuffle();
            $epicPacks = $epicPacks->shuffle();
            $rarePacks = $rarePacks->shuffle();
            $standardPacks = $standardPacks->shuffle();
        }

        $this->randArr['legendary'] = $legendaryPacks->toArray();
        $this->randArr['epic'] = $epicPacks->toArray();
        $this->randArr['rare'] = $rarePacks->toArray();
        $this->randArr['common'] = $standardPacks->toArray();
    }


    protected function getRandomFlame(){
        $shuffleAmount = random_int(10,100);
        for($i=0; $i < $shuffleAmount; $i++){
            $this->flamesRandom = $this->flamesRandom->shuffle();
        }
        return $this->flamesRandom->shift();
    }

    protected function getPackType($i){
        if($i < $this->packLegendaryCount) {
            return 'legendary';
        } else if($i < $this->packLegendaryCount + $this->packEpicCount) {
            return 'epic';
        } else if($i < $this->packLegendaryCount + $this->packEpicCount + $this->packRareCount) {
            return 'rare';
        }
        return 'common';
    }
    protected function getPackPos($i){
        if($i < $this->packLegendaryCount) {
            return $i;
        } else if($i < $this->packLegendaryCount + $this->packEpicCount) {
            return $i - $this->packLegendaryCount;
        } else if($i < $this->packLegendaryCount + $this->packEpicCount + $this->packRareCount) {
            return $i - ($this->packLegendaryCount + $this->packEpicCount);
        }
        return $i - ($this->packLegendaryCount + $this->packEpicCount + $this->packRareCount);
    }

    protected function randomizeArray($showErrors = false){


        $this->info('Reload from DB');
        $this->reloadFromDb();


        $this->randArr = [
            'legendary' => [],
            'epic' => [],
            'rare' => [],
            'common' => []
        ];


        $this->info('Rare Flames Distribution');

        for($i = 0; $i < $this->packCount; $i++){

            $flames = [];
            $type = $this->getPackType($i);

            if($type == 'legendary') {
                $temp = $this->flamesLegendary->shift();
                $flames[$temp->flow_id] = $temp->rarity;
            } else if($type == 'epic') {
                $temp = $this->flamesEpic->shift();
                $flames[$temp->flow_id] = $temp->rarity;
            } else if($type == 'rare') {
                $temp = $this->flamesRare->shift();
                $flames[$temp->flow_id] = $temp->rarity;
            }


            $packArr = [
                'type' =>  $type,
                'flames' => $flames,
                'components' => [],
                'numbers' => []
            ];

            $this->randArr[$type][] = $packArr;
        }


        $this->flamesRandom = $this->flamesLegendary->concat($this->flamesEpic)->concat($this->flamesRare)->concat($this->flamesCommon);



        $this->info('All Flames Distribution');

        for($i = 0; $i < $this->packCount; $i++){
            $type = $this->getPackType($i);
            $pos = $this->getPackPos($i);
            if($type == 'legendary') {
                for($j = 1; $j < $this->packFlameLegendaryCount; $j++) {
                    $temp = $this->getRandomFlame();
                    $this->randArr[$type][$pos]['flames'][$temp->flow_id] = $temp->rarity;
                }
            } else if($type == 'epic') {
                for($j = 1; $j < $this->packFlameEpicCount; $j++) {
                    $temp = $this->getRandomFlame();
                    $this->randArr[$type][$pos]['flames'][$temp->flow_id] = $temp->rarity;
                }
            } else if($type == 'rare') {
                for($j = 1; $j < $this->packFlameRareCount; $j++) {
                    $temp = $this->getRandomFlame();
                    $this->randArr[$type][$pos]['flames'][$temp->flow_id] = $temp->rarity;
                }
            } else {
                $temp = $this->getRandomFlame();
                $this->randArr[$type][$pos]['flames'][$temp->flow_id] = $temp->rarity;
            }
        }


        $this->info('Randomize Packs');

        $this->randomizePacks();


        $this->info('Fill Components');

        for($i = 0; $i < $this->packLegendaryCount; $i++){
            for($j = 0; $j < $this->packComponentLegendaryCount; $j++){
                $temp = $this->componentsRandom->shift();
                $this->randArr['legendary'][$i]['components'][$temp->flow_id] = $temp->rarity;
            }
        }

        for($i = 0; $i < $this->packEpicCount; $i++){
            for($j = 0; $j < $this->packComponentEpicCount; $j++) {
                $temp = $this->componentsRandom->shift();
                $this->randArr['epic'][$i]['components'][$temp->flow_id] = $temp->rarity;
            }
        }

        for($i = 0; $i < $this->packRareCount; $i++){
            for($j = 0; $j < $this->packComponentRareCount; $j++) {
                $temp = $this->componentsRandom->shift();
                $this->randArr['rare'][$i]['components'][$temp->flow_id] = $temp->rarity;
            }
        }

        for($i = 0; $i < $this->packCommonCount; $i++){
            for($j = 0; $j < $this->packComponentCommonCount; $j++) {
                $temp = $this->componentsRandom->shift();
                $this->randArr['common'][$i]['components'][$temp->flow_id] = $temp->rarity;
            }
        }



        $this->info('Randomize Packs');

        $this->randomizePacks();


        $this->info('Fill Numbers');

        for($i = 0; $i < $this->packLegendaryCount; $i++){
            for($j = 0; $j < $this->packNumberLegendaryCount; $j++){
                $temp = $this->numbersRandom->shift();
                $this->randArr['legendary'][$i]['numbers'][$temp->flow_id] = $temp->rarity;
            }
        }

        for($i = 0; $i < $this->packEpicCount; $i++){
            for($j = 0; $j < $this->packNumberEpicCount; $j++) {
                $temp = $this->numbersRandom->shift();
                $this->randArr['epic'][$i]['numbers'][$temp->flow_id] = $temp->rarity;
            }
        }

        for($i = 0; $i < $this->packRareCount; $i++){
            for($j = 0; $j < $this->packNumberRareCount; $j++) {
                $temp = $this->numbersRandom->shift();
                $this->randArr['rare'][$i]['numbers'][$temp->flow_id] = $temp->rarity;
            }
        }

        //------------------------------


        $this->info('Finish Randomize Array');


        if($showErrors) {
            $this->info('Flames left ' . $this->flamesRandom->count());
            $this->info('Components left ' . $this->componentsRandom->count());
            $this->info('Numbers left ' . $this->numbersRandom->count());
        }

    }





    protected function finalCheck($showErrors = false){

        $check = true;


        $checkPackFlameCommonCount = 0;
        $checkPackFlameRareCount = 0;
        $checkPackFlameEpicCount = 0;
        $checkPackFlameLegendaryCount = 0;

        $checkPackComponentCommonCount = 0;
        $checkPackComponentRareCount = 0;
        $checkPackComponentEpicCount = 0;
        $checkPackComponentLegendaryCount = 0;

        $checkPackNumberCount = 0;

        $arrRarities = ['legendary', 'epic', 'rare', 'common'];

        foreach($arrRarities as $tempRarity){
            foreach($this->randArr[$tempRarity] as $key => $pack) {
                foreach ($pack['flames'] as $rarity) {
                    if ($rarity == 'common') {
                        $checkPackFlameCommonCount++;
                    }
                    if ($rarity == 'rare') {
                        $checkPackFlameRareCount++;
                    }
                    if ($rarity == 'epic') {
                        $checkPackFlameEpicCount++;
                    }
                    if ($rarity == 'legendary') {
                        $checkPackFlameLegendaryCount++;
                    }
                }

                foreach ($pack['components'] as $rarity) {
                    if ($rarity == 'common') {
                        $checkPackComponentCommonCount++;
                    }
                    if ($rarity == 'rare') {
                        $checkPackComponentRareCount++;
                    }
                    if ($rarity == 'epic') {
                        $checkPackComponentEpicCount++;
                    }
                    if ($rarity == 'legendary') {
                        $checkPackComponentLegendaryCount++;
                    }
                }

                foreach ($pack['numbers'] as $rarity) {
                    $checkPackNumberCount++;
                }

                if($tempRarity == 'legendary'){
                    if (count($pack['flames']) != $this->packFlameLegendaryCount) {
                        if ($showErrors) {
                            $this->error('Invalid Legendary Flames count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['components']) != $this->packComponentLegendaryCount) {
                        if ($showErrors) {
                            $this->error('Invalid Legendary Component count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['numbers']) != $this->packNumberLegendaryCount) {
                        if ($showErrors) {
                            $this->error('Invalid Legendary Numbers count on Pack ' . $key . ' - Found ' . count($pack['numbers']) . ' Numbers');
                        }
                        $check = false;
                    }
                } else if($tempRarity == 'epic'){
                    if (count($pack['flames']) != $this->packFlameEpicCount) {
                        if ($showErrors) {
                            $this->error('Invalid Epic Flames count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['components']) != $this->packComponentEpicCount) {
                        if ($showErrors) {
                            $this->error('Invalid Epic Component count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['numbers']) != $this->packNumberEpicCount) {
                        if ($showErrors) {
                            $this->error('Invalid Epic Numbers count on Pack ' . $key . ' - Found ' . count($pack['numbers']) . ' Numbers');
                        }
                        $check = false;
                    }
                } else if($tempRarity == 'rare'){
                    if (count($pack['flames']) != $this->packFlameRareCount) {
                        if ($showErrors) {
                            $this->error('Invalid Rare Flames count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['components']) != $this->packComponentRareCount) {
                        if ($showErrors) {
                            $this->error('Invalid Rare Component count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['numbers']) != $this->packNumberRareCount) {
                        if ($showErrors) {
                            $this->error('Invalid Rare Numbers count on Pack ' . $key . ' - Found ' . count($pack['numbers']) . ' Numbers');
                        }
                        $check = false;
                    }
                } else {
                    if (count($pack['flames']) != $this->packFlameCommonCount) {
                        if ($showErrors) {
                            $this->error('Invalid Common Flames count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['components']) != $this->packComponentCommonCount) {
                        if ($showErrors) {
                            $this->error('Invalid Common Component count on Pack ' . $key . ' - Found ' . count($pack['components']) . ' Components');
                        }
                        $check = false;
                    }
                    if (count($pack['numbers']) != $this->packNumberCommonCount) {
                        if ($showErrors) {
                            $this->error('Invalid Common Numbers count on Pack ' . $key . ' - Found ' . count($pack['numbers']) . ' Numbers');
                        }
                        $check = false;
                    }
                }
            }
        }







        /*
        if($showErrors) {

            if ($checkPackComponentCommonCount != $this->packComponentCommonTotal) {
                $this->error('Invalid Total Common Component for Packs. Found ' . $checkPackComponentCommonCount . ' - Expected ' . $this->packComponentCommonTotal . ' = Diff ' . ($checkPackComponentCommonCount - $this->packComponentCommonTotal));
            }
            if ($checkPackComponentRareCount != $this->packComponentRareTotal) {
                $this->error('Invalid Total Rare Component for Packs. Found ' . $checkPackComponentRareCount . ' - Expected ' . $this->packComponentRareTotal . ' = Diff ' . ($checkPackComponentRareCount - $this->packComponentRareTotal));
            }
            if ($checkPackComponentEpicCount != $this->packComponentEpicTotal) {
                $this->error('Invalid Total Epic Component for Packs. Found ' . $checkPackComponentEpicCount . ' - Expected ' . $this->packComponentEpicTotal . ' = Diff ' . ($checkPackComponentEpicCount - $this->packComponentEpicTotal));
            }
            if ($checkPackComponentLegendaryCount != $this->packComponentLegendaryTotal) {
                $this->error('Invalid Total Legendary Component for Packs. Found ' . $checkPackComponentLegendaryCount . ' - Expected ' . $this->packComponentLegendaryTotal . ' = Diff ' . ($checkPackComponentLegendaryCount - $this->packComponentLegendaryTotal));
            }


            if ($checkPackFlameCommonCount != $this->packFlameCommonTotal) {
                $this->error('Invalid Total Common Flame for Packs. Found ' . $checkPackFlameCommonCount . ' - Expected ' . $this->packFlameCommonTotal . ' = Diff ' . ($checkPackFlameCommonCount - $this->packFlameCommonTotal));
            }
            if ($checkPackFlameRareCount != $this->packFlameRareTotal) {
                $this->error('Invalid Total Rare Flame for Packs. Found ' . $checkPackFlameRareCount . ' - Expected ' . $this->packFlameRareTotal . ' = Diff ' . ($checkPackFlameRareCount - $this->packFlameRareTotal));
            }
            if ($checkPackFlameEpicCount != $this->packFlameEpicTotal) {
                $this->error('Invalid Total Epic Flame for Packs. Found ' . $checkPackFlameEpicCount . ' - Expected ' . $this->packFlameEpicTotal . ' = Diff ' . ($checkPackFlameEpicCount - $this->packFlameEpicTotal));
            }
            if ($checkPackFlameLegendaryCount != $this->packFlameLegendaryTotal) {
                $this->error('Invalid Total Legendary Flame for Packs. Found ' . $checkPackFlameLegendaryCount . ' - Expected ' . $this->packFlameLegendaryTotal . ' = Diff ' . ($checkPackFlameLegendaryCount - $this->packFlameLegendaryTotal));
            }


            if ($checkPackNumberCount != $this->packNumberTotal) {
                $this->error('Invalid Total Number for Packs. Found ' . $checkPackNumberCount . ' - Expected ' . $this->packNumberTotal . ' = Diff ' . ($checkPackNumberCount - $this->packNumberTotal));
            }


            $this->info('All checks completed!');
        }


        if($checkPackComponentCommonCount != $this->packComponentCommonTotal ||
            $checkPackComponentRareCount != $this->packComponentRareTotal ||
            $checkPackComponentEpicCount != $this->packComponentEpicTotal ||
            $checkPackComponentLegendaryCount != $this->packComponentLegendaryTotal
        ){
            $check = false;
        }
        if($checkPackFlameCommonCount != $this->packFlameCommonTotal ||
            $checkPackFlameRareCount != $this->packFlameRareTotal ||
            $checkPackFlameEpicCount != $this->packFlameEpicTotal ||
            $checkPackFlameLegendaryCount != $this->packFlameLegendaryTotal
        ){
            $check = false;
        }
        if($checkPackNumberCount != $this->packNumberTotal){
            $check = false;
        }
        */

        return $check;
    }


}
