<?php

namespace App\Console\Commands;

use App\Jobs\DiscordBots;
use Illuminate\Console\Command;

class TestDiscordRateLimitCommand extends Command
{
    protected $signature = 'discord:test {--count=5 : Number of test messages to send}';
    protected $description = 'Test Discord rate limiting by sending multiple messages';

    public function handle(): int
    {
        $count = (int) $this->option('count');
        
        $this->info("Sending {$count} test messages to Discord...");

        for ($i = 1; $i <= $count; $i++) {
            DiscordBots::dispatch([
                'message' => "Test message #{$i}",
                'details' => [
                    'test_number' => $i,
                    'timestamp' => now()->toDateTimeString(),
                    'type' => 'Rate Limit Test'
                ]
            ]);
            
            $this->line("Queued message #{$i}");
        }

        $this->info("All {$count} messages have been queued for Discord delivery.");
        $this->info("Run 'php artisan discord:status' to check rate limiting status.");
        $this->info("Run 'php artisan queue:work' to process the queued messages.");

        return 0;
    }
}