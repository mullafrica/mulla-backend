<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Interfaces\ISafeHavenService;

class SafeHavenService implements ISafeHavenService
{
    public function token()
    {
        return Cache::remember('safe_haven_access_token', 3600, function () { // Storing this for 1hr
            return $this->getAccessToken();
        });
    }

    private function getAccessToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => env('SAFE_HAVEN_CLIENT_ID'),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => env('SAFE_HAVEN_CLIENT_ASSERTION'),
        ];

        $res = Http::post('https://api.safehavenmfb.com/oauth2/token', $data);

        return $res->json()['access_token'] ?? null;
    }

    public function buyElectricity() {}
}
