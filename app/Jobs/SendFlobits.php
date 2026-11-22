<?php

namespace App\Jobs;

use App\Models\Sportbit;
use App\Models\Pack;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;
use Str;

class SendFlobits extends BaseJob
{

    protected $components;
    protected $address;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($components, $address)
    {
        $this->components = $components;
        $this->address =  Str::of($address)->trim();
        parent::__construct();
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {

        $output = Flow::transaction('./transactions/act/components_send.cdc')
            ->argArrayInt($this->components)
            ->argAddress($this->address)
            ->signer(config('sportvatar.signer'))
            ->run();
        info(print_r($output, true));
    }
}
