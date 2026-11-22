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
    protected $sendSender;
    protected $sendRcs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Subscriber $subscriber, $sendSender = true, $sendRcs = false)
    {
        $this->subscriber = $subscriber;
        $this->sendSender = (bool) $sendSender;
        // The caller decides (consent given in this request, first time, RCS forwarding enabled).
        $this->sendRcs = (bool) $sendRcs;
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

        if($this->sendSender && $this->subscriber->sportvatar){

        Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.sender_mail.token'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post(config('services.sender_mail.url'), [
            'email' => $this->subscriber->email,
            'groups' => ['bDQOjk']
        ]);

        }

        if($this->sendRcs && $this->subscriber->rcs && config('sportvatar.rcsRegistrationEnabled')) {
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
