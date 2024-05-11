<?php

namespace App\Jobs;

use App\Traits\Reusables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DiscordBots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Reusables;

    public $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendToDiscord($this->data['message'] ?? 'Exception occured.');
    }
}
