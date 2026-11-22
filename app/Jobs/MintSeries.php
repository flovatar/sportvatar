<?php

namespace App\Jobs;

use App\Models\SportvatarSeries;
use Flow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MintSeries extends BaseJob
{
    protected $series;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SportvatarSeries $series)
    {
        $this->series = $series;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     *
     */
    public function handle()
    {
        Flow::transaction('./transactions/create/series.cdc')
            ->argString($this->series->name)
            ->argString(''.$this->series->description)
            ->argString($this->series->svg_prefix)
            ->argString($this->series->svg_suffix)
            ->argArrayInt32($this->series->layers->pluck('id'))
            ->argArrayString($this->series->layers->pluck('name'))
            ->argArrayBool($this->series->layers->pluck('isAccessory'))
            ->argArrayInt32($this->series->colors->keys())
            ->argArrayString($this->series->colors->values())
            ->argInt($this->series->max_mintable)
            ->signer(config('sportvatar.signer'))
            ->run();
    }
}
