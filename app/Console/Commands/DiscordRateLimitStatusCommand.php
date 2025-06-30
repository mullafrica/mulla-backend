<?php

namespace App\Console\Commands;

use App\Services\DiscordRateLimiterService;
use Illuminate\Console\Command;

class DiscordRateLimitStatusCommand extends Command
{
    protected $signature = 'discord:status';
    protected $description = 'Check Discord rate limiting status';

    public function handle(DiscordRateLimiterService $rateLimiter): int
    {
        $status = $rateLimiter->getRateLimitStatus();

        $this->info('Discord Rate Limiter Status:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current requests this minute', $status['current_count']],
                ['Max requests per minute', $status['max_per_minute']],
                ['Messages in batch queue', $status['batch_queue_size']],
                ['Can send now', $status['can_send'] ? 'Yes' : 'No'],
            ]
        );

        return 0;
    }
}