<?php

namespace App\Jobs;

use App\Models\Sportbit;
use App\Models\SportvatarTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;

class MintComponentFake extends BaseJob
{
    protected $template;
    protected $quantity;
    protected $batch;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SportvatarTemplate $template, $quantity = 1, $batch = 1)
    {
        $this->template = $template;
        $this->quantity = $quantity;
        $this->batch = $batch;
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        for($i = 0; $i < $this->quantity; $i++){
            Sportbit::create(
                [
                    'flow_id' => ($this->template->id*100000) + $i + $this->batch,
                    'flow_address' => config('services.flow.address'),
                    'sportvatar_template_id' => $this->template->id,
                    'flow_metadata' => []
                ]
            );
        }
    }
}
