<?php

namespace App\Jobs;

use App\Models\SportvatarTemplate;
use Flow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MintTemplate extends BaseJob
{
    protected $template;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SportvatarTemplate $template)
    {
        $this->template = $template;
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
        Flow::transaction('./transactions/create/template.cdc')
            ->argString(''.$this->template->name)
            ->argString(''.$this->template->description)
            ->argInt($this->template->sportvatarSeries->flow_id)
            ->argInt32($this->template->layer)
            ->argString($this->template->sport)
            ->argString($this->template->rarity)
            ->argString($this->template->svg)
            ->argInt($this->template->max_mintable)
            ->signer(config('sportvatar.signer'))
            ->run();
    }
}
