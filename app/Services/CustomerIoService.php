<?php

namespace App\Services;

use App\Jobs\DiscordBots;
use Illuminate\Support\Facades\Http;

class CustomerIoService
{
    protected $siteId;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->siteId = config('services.customerio.site_id');
        $this->apiKey = config('services.customerio.api_key');
        $this->baseUrl = 'https://track.customer.io/api/v1';
    }

    /**
     * Identify a user in Customer.io
     *
     * @param array $user
     * @return \Illuminate\Http\Client\Response
     */
    public function identifyUser(array $user)
    {
        if (env("APP_ENV") === "production") {
            $url = "{$this->baseUrl}/customers/{$user['user_id']}";

            Http::withBasicAuth($this->siteId, $this->apiKey)
                ->put($url, [
                    'id'         => $user['user_id'],
                    'email'      => $user['email'],
                    'first_name' => $user['firstname'],
                    'last_name'  => $user['lastname'],
                    'phone'      => $user['phone'],
                    'created_at' => strtotime($user['created_at']),
                ]);

            DiscordBots::dispatch(['message' => 'User identified in Customer.io']);
        }
    }

    /**
     * Track an event for a user in Customer.io
     *
     * @param array $user
     * @param string $event
     * @return \Illuminate\Http\Client\Response
     */
    public function trackEvent(array $user, string $event)
    {
        if (env("APP_ENV") === "production") {
            $url = "{$this->baseUrl}/customers/{$user['email']}/events";

            Http::withBasicAuth($this->siteId, $this->apiKey)
                ->post($url, [
                    'name'  => $event,
                    'data'  => $user
                ]);

            DiscordBots::dispatch(['message' => 'User (' . $user['email'] . ') event (' . $event . ') tracked in Customer.io']);
        }
    }
}
