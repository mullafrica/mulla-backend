<?php

namespace App\Traits;

use GuzzleHttp\Client;

trait Reusables
{
    protected function sendToDiscord($message)
    {
        $webhookUrl = env('DISCORD_WEBHOOK_URL');

        if (env('APP_ENV') !== 'production') {
            $webhookUrl = env('DISCORD_WEBHOOK_URL_DEV');
        }

        $client = new Client();  // Instantiate Guzzle HTTP client

        $client->post($webhookUrl, [
            'json' => [
                'content' => $message
            ]
        ]);
    }
}
