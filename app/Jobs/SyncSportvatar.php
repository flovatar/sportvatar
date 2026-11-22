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

class SyncSportvatar extends BaseJob
{

    protected $id;
    protected $address;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $address)
    {
        $this->id = $id;
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

        $this->syncSportvatarFromFlowAddress($this->id, $this->address);
        info('Finished syncing Sportvatar '.$this->id);
    }
}
