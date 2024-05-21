<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

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

    public function getUserDetails()
    {
        $agent = new Agent();
        $ip = request()->ip();
        $location = Location::get($ip);

        $browser = $agent->browser();
        $browserVersion = $agent->version($browser);
        $platform = $agent->platform();
        $platformVersion = $agent->version($platform);

        return [
            'ip_address' => $ip,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'platform' => $platform,
            'platform_version' => $platformVersion,
            'location' => $location ? [
                'country' => $location->countryName,
                'region' => $location->regionName,
                'city' => $location->cityName,
                'zip_code' => $location->zipCode,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'timezone' => $location->timezone,
            ] : null,
        ];
    }
}
