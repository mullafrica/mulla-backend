<?php

namespace App\Traits;

use App\Services\DiscordRateLimiterService;
use GuzzleHttp\Client;
use Stevebauman\Location\Facades\Location;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Support\Facades\Http;

trait Reusables
{
    protected function sendToDiscord($message, $details = [])
    {
        // Send immediately without batching
        $this->sendToDiscordDirect($message, $details);
    }

    protected function sendToDiscordBatched($message, $details = [])
    {
        // Use batching system for bulk transfers
        $rateLimiter = app(DiscordRateLimiterService::class);
        
        $rateLimiter->queueMessage([
            'message' => $message,
            'details' => $details
        ]);
    }

    protected function sendToDiscordDirect($message, $details = [])
    {
        $webhookUrl = env('DISCORD_WEBHOOK_URL');

        if (env('APP_ENV') !== 'production') {
            $webhookUrl = env('DISCORD_WEBHOOK_URL_DEV');
        }

        $client = new Client();  // Instantiate Guzzle HTTP client

        $payload = ['content' => $message];

        // If details exist, format them as an embed
        if (!empty($details)) {
            $embed = [
                'title' => 'Details',
                'color' => 0x3498db, // Blue color
                'fields' => [],
                'timestamp' => now()->toISOString()
            ];

            foreach ($details as $key => $value) {
                // Skip empty values
                if ($value === null || $value === '') continue;
                
                $embed['fields'][] = [
                    'name' => ucwords(str_replace('_', ' ', $key)),
                    'value' => is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : (string)$value,
                    'inline' => true
                ];
            }

            $payload['embeds'] = [$embed];
        }

        $client->post($webhookUrl, [
            'json' => $payload
        ]);
    }

    public function getUserDetails($ip)
    {
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
                    'latitude' => '6',
                    'longitude' => '7',
                    // 'timezone' => 'Africa/Lagos',
                ],
            ];
        }

        $location = Location::get($ip);

        $browser = Browser::browserFamily();
        // $browserVersion = Browser::browserVersion();
        $platform = Browser::platformName();
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
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                // 'timezone' => $location->timezone,
            ] : null,
        ];
    }
}
