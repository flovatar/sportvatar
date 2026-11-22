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
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SignPack extends BaseJob
{
    use Dispatchable;

    protected $pack;
    protected $address;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Pack $pack)
    {
        $this->pack = $pack;
        parent::__construct();
    }

    public function getName(){
        return 'SignPack';
    }
    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {
        $cmd = [
            'go',
            'run',
            './user_signature/main.go',
            '-arg='.$this->pack->secret
        ];

        info('Executing Sign Pack with params: '.print_r($cmd, true));
        $process = new Process($cmd, base_path('go/examples'));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $res = json_decode($process->getOutput());
        $signature = is_array($res) && count($res)>0 ? $res[0] : '';
        //info($signature);
        if($signature) {
            $this->pack->update([
                'signature' => $signature
            ]);

            info('Executed Signature for Pack '.$this->pack->id);
        }

    }
}
