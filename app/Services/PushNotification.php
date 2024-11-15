<?php

namespace App\Services;

use App\Jobs\PushNotificationJob;
use Illuminate\Support\Facades\Http;

class PushNotification
{
    public function send($data)
    {
        if (is_array($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                $data = array_map(function ($item) {
                    $item['badge'] = 1;
                    $item['channelId'] = 'default';
                    $item['sound'] = 'default';
                    return $item;
                }, $data);
            } else {
                $data['badge'] = 1;
                $data['channelId'] = 'default';
                $data['sound'] = 'default';
            }
        }

        PushNotificationJob::dispatch($data);
    }
}