<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class PushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
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
        $response = Http::post('https://exp.host/--/api/v2/push/send', $this->data);
        DiscordBots::dispatch([
            'message' => '📱 **Push notification sent**',
            'details' => [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'notification_data' => $this->data,
                'timestamp' => now()->toDateTimeString()
            ]
        ]);
    }
}
