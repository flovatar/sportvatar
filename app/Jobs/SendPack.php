<?php

namespace App\Jobs;

use App\Models\Sportbit;
use App\Models\Pack;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;
use Str;

class SendPack extends BaseJob
{

    protected $pack;
    protected $address;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Pack $pack, $address)
    {
        $this->pack = $pack;
        $this->address = Str::of($address)->trim();
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
        $output = Flow::transaction('./transactions/act/pack_send.cdc')
            ->argInt($this->pack->flow_id)
            ->argAddress($this->address)
            ->signer(config('sportvatar.signer'))
            ->run();
        info(print_r($output, true));
    }
}
