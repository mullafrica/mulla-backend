<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Stevebauman\Location\Facades\Location;
use hisorange\BrowserDetect\Facade as Browser;

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

    public function getUserDetails($ip, $userAgent)
    {
        $b = Browser::parse($userAgent);

        if (env('APP_ENV') !== 'production') {
            return [
                // 'ip_address' => request()->ip(),
                'browser' => 'Safari',
                // 'browser_version' => '14.0',
                'platform' => 'macOS',
                // 'platform_version' => '11.0',
                'location' => [
                    'country' => 'Nigeria',
                    // 'region' => 'Lagos',
                    'city' => 'Ike',
                    // 'zip_code' => '100100',
                    // 'latitude' => '6',
                    // 'longitude' => '7',
                    // 'timezone' => 'Africa/Lagos',
                ],
            ];
        }

        $location = Location::get($ip);

        $browser = $b->browserFamily();
        // $browserVersion = $browser::browserVersion();
        $platform = $b->platformName();
        // $platformVersion = Browser::platformVersion();

        return [
            // 'ip_address' => $ip,
            'browser' => $browser,
            // 'browser_version' => $browserVersion,
            'platform' => $platform,
            // 'platform_version' => $platformVersion,
            'location' => $location ? [
                'country' => $location->countryName,
                // 'region' => $location->regionName,
                'city' => $location->cityName,
                // 'zip_code' => $location->zipCode,
                // 'latitude' => $location->latitude,
                // 'longitude' => $location->longitude,
                // 'timezone' => $location->timezone,
            ] : null,
        ];
    }
}
