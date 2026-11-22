<?php

namespace App\Jobs;

use App\Models\SportvatarTemplate;
use App\Models\Subscriber;
use Flow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Str;

class AddMailSubscriber extends BaseJob
{
    protected $subscriber;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
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

        if($this->subscriber->sportvatar){

        Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.sender_mail.token'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post(config('services.sender_mail.url'), [
            'email' => $this->subscriber->email,
            'groups' => ['bDQOjk']
        ]);

        }

        if($this->subscriber->rcs) {
            //Send to RCS RUNA

            Http::withHeaders([
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json',
            ])->post('https://sslweb.rcs.it/rsle/idmgmt/' . config('sportvatar.rcsProviderId') . '/register.json', [
                'providerId' => config('sportvatar.rcsProviderId'),
                'applicationId' => config('sportvatar.rcsAppId'),
                'user' => [
                    "email" => $this->subscriber->email,
                    "password" => Str::random(20),
                    "userType" => "F",
                    "privacy" => [
                        "rcs" => true,
                        "others" => false,
                        "tracking" => false
                    ]
                ]
            ]);
        }

    }
}
