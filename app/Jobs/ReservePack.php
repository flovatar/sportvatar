<?php

namespace App\Jobs;

use App\Models\Sportbit;
use App\Models\Pack;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Flow;

class ReservePack extends BaseJob
{

    protected $packName;
    protected $id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($packName, $id)
    {
        $this->packName = $packName;
        $this->id = $id;
        $this->onConnection('database')->onQueue('single');
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
        $pack = Pack::readyToSell()->whereName($this->packName)->where('user_id', $this->id)->first();

        if(!$pack) {
            Pack::readyToSell()->whereNull('user_id')->whereNull('assigned_at')->whereName($this->packName)->limit(1)->update([
                'user_id' => $this->id,
                'assigned_at' => Carbon::now()
            ]);
        }
    }
}
