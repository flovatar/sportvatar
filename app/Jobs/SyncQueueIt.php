<?php

namespace App\Jobs;


use App\Models\Event;
use App\Models\Sync;
use Flow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Storage;

class SyncQueueIt extends BaseJob
{

    public function getName(){
        return 'SyncQueueIt';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $headers = [
            'Host' => 'queue-it.net',
            'api-key' => config('queueit.apiKey')
        ];
        $config = Http::withHeaders($headers)->get(config('queueit.url'));

        Storage::put('queueit-config.json', $config->getBody());
    }


}
