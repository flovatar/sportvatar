<?php

namespace App\Jobs;

use App\Models\Sportvatar;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Storage;
use Illuminate\Http\File;
use Str;
use DeDmytro\CloudflareImages\Facades\CloudflareApi;
use DeDmytro\CloudflareImages\Http\Responses\DetailsResponse;
use DeDmytro\CloudflareImages\Http\Entities\Image;

class ExportImage extends BaseJob
{

    protected $type;
    protected $sportvatarId;
    protected $useBackground;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sportvatarId, $useBackground = true, $type = 'gif')
    {
        $this->sportvatarId = $sportvatarId;
        $this->useBackground = $useBackground;
        $this->type = $type;
        $this->onQueue('images');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        info('CREATING IMAGE FOR SPORTVATAR '.$this->sportvatarId);
        $nobg = $this->useBackground ? '' : 'nobg/';
        $nobgOut = $this->useBackground ? '' : '-nobg';

        $appBasePath = 'app/public/sportvatar/'.$this->type;

        $pngDir = 'public/sportvatar/'.$this->type.'/'.$this->sportvatarId;
        $pngNobgDir = $pngDir.'-nobg';

        Storage::makeDirectory('public/sportvatar/'.$this->type);
        if($this->type == 'png'){
            Storage::makeDirectory($pngDir);
            Storage::makeDirectory($pngNobgDir);
        }
        if($this->type == 'gif'){
            Storage::makeDirectory('public/sportvatar/'.$this->type.'/temp/'.$this->sportvatarId.$nobgOut);
        }

        if($this->type == 'svg'){

            $sportvatar = Sportvatar::whereFlowId($this->sportvatarId)->first();
            if($sportvatar){
                $baseSvgPath = 'public/sportvatar/'.$this->type.'/'.$this->sportvatarId;
                if($this->useBackground){
                    Storage::put($baseSvgPath.'.svg', $sportvatar->full_svg);
                } else {
                    Storage::put($baseSvgPath.'-nobg.svg', $sportvatar->full_svg_no_bg);
                }
            }
        } else {
            $cmd = ['webgif'];

            $cmd[] = '-u';
            $cmd[] = 'https://sportvatar.com/api/image/'.$nobg.$this->sportvatarId;

            $cmd[] = '-o';
            $cmd[] = $this->sportvatarId.$nobgOut;

            $cmd[] = '-d';
            $cmd[] = '40';
            $cmd[] = '-f';
            $cmd[] = '75';
            $cmd[] = '-q';
            $cmd[] = ($this->type == 'gif') ? '50' : '100';
            $cmd[] = '-t';
            $cmd[] = $this->type;

            //info(print_r($cmd,true));

            $process = new Process($cmd, storage_path('app/public/sportvatar/'.$this->type));
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            if($this->type == 'gif'){
                Storage::deleteDirectory('public/sportvatar/'.$this->type.'/temp/'.$this->sportvatarId.$nobgOut);
            }
        }




        $filePath = $appBasePath.'/'.$this->sportvatarId.$nobgOut.'.'.$this->type;
        Storage::disk('s3')->putFileAs($this->type, new File(storage_path($filePath)), $this->sportvatarId.$nobgOut.'.'.$this->type);
        Storage::delete('public/sportvatar/'.$this->type.'/'.$this->sportvatarId.$nobgOut.'.'.$this->type);


        if($this->type == 'png') {
            //$this->movePngImages($pngDir);
            //$this->movePngImages($pngNobgDir);

            $this->createVideo();
            $filePathMp4 = $appBasePath.'/'.$this->sportvatarId.$nobgOut.'/'.$this->sportvatarId.'.mp4';
            Storage::disk('s3')->putFileAs('mp4', new File(storage_path($filePathMp4)), $this->sportvatarId.$nobgOut.'.mp4');
            Storage::deleteDirectory('public/sportvatar/png/'.$this->sportvatarId.$nobgOut);
        }
        //info($process->getOutput());

        return true;
    }

    protected function movePngImages($folder){

        $files = Storage::allFiles($folder);
        $nobg = $this->useBackground ? '' : '-nobg';
        foreach($files as $file) {
            if (!Str::contains($file, ['.DS_Store'])) {
                $name = str_replace($folder.'/','', $file);
                Storage::disk('s3')->putFileAs('png/'.$this->sportvatarId.$nobg, new File(storage_path('app/'.$file)), $name);
            }
        }
        Storage::deleteDirectory($folder);
    }


    protected function createVideo(){
        $cmd = ['ffmpeg'];

        $cmd[] = '-r';
        $cmd[] = '25';
        $cmd[] = '-f';
        $cmd[] = 'image2';
        $cmd[] = '-s';
        $cmd[] = '480x480';
        $cmd[] = '-i';
        $cmd[] = '%d.png';
        $cmd[] = '-vcodec';
        $cmd[] = 'libx264';
        $cmd[] = '-crf';
        $cmd[] = '25';
        $cmd[] = '-pix_fmt';
        $cmd[] = 'yuv420p';

        $cmd[] = $this->sportvatarId.'.mp4';

        //info(print_r($cmd,true));

        $nobg = $this->useBackground ? '' : '-nobg';
        $process = new Process($cmd, storage_path('app/public/sportvatar/'.$this->type.'/'.$this->sportvatarId.$nobg));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
