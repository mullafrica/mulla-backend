<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait Reusables
{
    protected function sendToDiscord($message)
    {
        $webhookUrl = env('DISCORD_WEBHOOK_URL');

        $client = new Client();  // Instantiate Guzzle HTTP client

        $client->post($webhookUrl, [
            'json' => [
                'content' => $message
            ]
        ]);
    }
}
