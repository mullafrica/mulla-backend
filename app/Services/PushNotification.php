<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PushNotification
{
    public function send($data)
    {
        Http::post('https://exp.host/--/api/v2/push/send', [
            "to" => $data['to'],
            "title" => $data['title'],
            "body" => $data['body'],
            "badge" => 1,
            // "channelId" => 'default',
        ]);
    }
}