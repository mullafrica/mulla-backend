<?php

namespace App\Jobs;

use App\Services\DiscordRateLimiterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDiscordBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 30;
    public $backoff = [10, 30, 60];

    public function handle(DiscordRateLimiterService $rateLimiter): void
    {
        try {
            $rateLimiter->processBatch();
        } catch (\Exception $e) {
            Log::error('ProcessDiscordBatch job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDiscordBatch job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}