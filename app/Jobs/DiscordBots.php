<?php

namespace App\Jobs;

use App\Services\DiscordRateLimiterService;
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
    
    // Job configuration to prevent infinite retries
    public $tries = 3;
    public $timeout = 30;
    public $maxExceptions = 3;

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
    public function handle(DiscordRateLimiterService $rateLimiter): void
    {
        $message = $this->data['message'] ?? 'Exception occured.';
        $details = $this->data['details'] ?? [];
        
        $rateLimiter->queueMessage([
            'message' => $message,
            'details' => $details
        ]);
    }
}
