<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ExportAllImages extends BaseJob
{

    protected $sportvatarId;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sportvatarId)
    {
        $this->sportvatarId = $sportvatarId;
        $this->onQueue('images');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // New static renderer (resvg -> R2): PNG/JPG/og.
        RenderItemImages::queue('sportvatar', $this->sportvatarId);

        if(!config('sportvatar.imageExportEnabled')){
            info('Image export disabled (SPORTVATAR_IMAGE_EXPORT_ENABLED=false), skipping '.static::class);
            return true;
        }

        ExportImage::dispatch($this->sportvatarId, true, 'svg');
        //ExportImage::dispatch($this->sportvatarId, false, 'svg');
        ExportImage::dispatch($this->sportvatarId,true, 'gif');
        //ExportImage::dispatch($this->sportvatarId, false, 'gif');
        ExportImage::dispatch($this->sportvatarId, true, 'png');
        //ExportImage::dispatch($this->sportvatarId, false, 'png');

        return true;
    }
}
