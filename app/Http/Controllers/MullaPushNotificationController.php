<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PushNotification;
use Illuminate\Http\Request;

class MullaPushNotificationController extends Controller
{
    public function sendNotification(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $data = $request->only([
            'email',
            'title',
            'body',
        ]);

        $fcmToken = User::where('email', $request->email)->value('fcm_token');

        if (!$fcmToken) {
            return response()->json([
                'message' => 'FCM token not found.'
            ], 404);
        }

        $data = [
            'to' => $fcmToken,
            'title' => $data['title'],
            'body' => $data['body'],
        ];

        $notification = new PushNotification();
        $notification->send($data);

        return response()->json([
            'message' => 'Notification sent.'
        ], 200);
    }

    public function sendNotificationToAll(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'body' => 'required',
        ]);

        $data = $request->only([
            'title',
            'body',
        ]);

        $fcmTokens = User::whereNotNull('fcm_token')->limit(99)->pluck('fcm_token')->toArray();
        $counter = 0;
        $totalSent = 0;

        while (count($fcmTokens) > 0) {
            $data = array_map(function ($token) use ($data) {
                return [
                    'to' => $token,
                    'title' => $data['title'],
                    'body' => $data['body'],
                ];
            }, $fcmTokens);

            $notification = new PushNotification();
            $notification->send($data);

            $counter += 99;
            $totalSent += count($fcmTokens);

            if ($counter >= User::whereNotNull('fcm_token')->count()) {
                break;
            }

            $fcmTokens = User::whereNotNull('fcm_token')->skip($counter)->limit(99)->pluck('fcm_token')->toArray();
        }

        return response()->json([
            'message' => 'Notifications sent to ' . $totalSent . ' users.'
        ], 200);
    }
}
