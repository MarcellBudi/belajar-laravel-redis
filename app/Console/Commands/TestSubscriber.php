<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Redis;
use Illuminate\Console\Command;

class TestSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        // echo "Helllo Marcell";
        Redis::subscribe(["channel-1", "channel-2"], function (string $message){
            echo $message . PHP_EOL;
        });
    }
}
