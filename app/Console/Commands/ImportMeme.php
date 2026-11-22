<?php

namespace App\Console\Commands;

use App\Models\MemeTemplate;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Str;

class ImportMeme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:import-meme {folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the Meme templates';

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
        $files = Storage::disk('localpublic')->allFiles('images/'.$this->argument('folder'));
        foreach($files as $file){
            if(!Str::contains($file, ['.DS_Store'])){
                $name = str_replace('images/'.$this->argument('folder').'/','', $file);
                $fullName = str_replace('.jpeg', '', $name);

                $checkImage = str_replace('images/', '', $name);

                if($checkImage == $name){
                    $this->info('Importing '.$name);
                    MemeTemplate::updateOrCreate([
                            'name' => $fullName,
                            'image' => $name
                        ]
                    );
                }


            }
        }

        $this->info('All done!');
    }

}
