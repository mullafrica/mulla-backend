<?php

namespace App\Services;

use App\Jobs\PushNotificationJob;
use Illuminate\Support\Facades\Http;

class PushNotification
{
    public function send($data)
    {
        // if (is_array($data)) {
        //     $data = array_map(function ($item) {
        //         $item['badge'] = 1;
        //         $item['channelId'] = 'default';
        //         $item['sound'] = 'default';
        //         return $item;
        //     }, $data);
        // }

        PushNotificationJob::dispatch($data);
    }
}