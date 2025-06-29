<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Interfaces\ISafeHavenService;

class SafeHavenService implements ISafeHavenService
{
    public function token()
    {
        return Cache::remember('safe_haven_access_token', 2000, function () { // Store for ~33 minutes (token expires in 40 minutes)
            return $this->getAccessToken();
        });
    }

    private function getAccessToken()
    {
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => env('SAFE_HAVEN_CLIENT_ID', '7e36708c22981a084fff541768f0a33e'),
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => env('SAFE_HAVEN_CLIENT_ASSERTION', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL211bGxhLm1vbmV5Iiwic3ViIjoiN2UzNjcwOGMyMjk4MWEwODRmZmY1NDE3NjhmMGEzM2UiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzM5OTU4MzQyLCJleHAiOjE3NzE0OTM2NDV9.JEyVWS82VscoErhhuJ2MW9qAnWWuHFsX168_Q6o0HjJR4xDaXIEm7tSEbkbvc-x-cnM9AYi30LQqyI24nFxvvY2rESGu6uG2BA0eIct-0HJHpG9Qr39ff8T_e107okPL5zMfFyPDtfaLSxAxJEWPk7moJD0pNprjF7PP6LrGdaY'),
        ];

        $res = Http::withOptions(['timeout' => 30])
            ->post('https://api.safehavenmfb.com/oauth2/token', $data);

        if (!$res->successful()) {
            throw new \Exception('Failed to get SafeHaven access token: ' . $res->body());
        }

        return $res->json()['access_token'] ?? null;
    }

    public function buyElectricity($meterNumber, $amount, $vendType = 'PREPAID')
    {
        $accessToken = $this->token();
        
        if (!$accessToken) {
            throw new \Exception('Failed to obtain SafeHaven access token');
        }

        $response = Http::withToken($accessToken)
            ->withOptions(['timeout' => 120])
            ->post('https://api.safehavenmfb.com/vas/pay/utility', [
                'serviceCategoryId' => '61efac35da92348f9dde5f77',
                'amount' => intval($amount * 100), // Convert to kobo
                'channel' => 'WEB',
                'debitAccountNumber' => env('SAFE_HAVEN_DEBIT_ACCOUNT', '0111124637'),
                'meterNumber' => $meterNumber,
                'vendType' => strtoupper($vendType)
            ]);

        if (!$response->successful()) {
            throw new \Exception('SafeHaven API request failed: ' . $response->body());
        }

        return $response->json();
    }
}
