<?php

namespace App\Console\Commands;

use App\Models\Sync;
use Illuminate\Console\Command;
use Flow;

class SyncBlockHeight extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sportvatar:sync-block-height';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs the block height';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        //info('Syncing all events');

        $latestHeight = $this->getLatestBlockHeight();
        $this->info('Syncing block height '.$latestHeight);

        Sync::whereNotNull('name')->update([
            'height' => $latestHeight
        ]);

        $this->info('Synced block height '.$latestHeight);

        return self::SUCCESS;
    }

    protected function getLatestBlockHeight(){
        $block = Flow::getLatestBlock();
        $items = json_decode($block);
        return ($items && isset($items->height)) ? $items->height : null;
    }
}
