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

        $totalUsers = User::whereNotNull('fcm_token')->count();
        $counter = 0;
        $totalSent = 0;

        while ($counter < $totalUsers) {
            // Get tokens in batches of 99
            $fcmTokens = User::whereNotNull('fcm_token')
                ->skip($counter)
                ->limit(99)
                ->pluck('fcm_token')
                ->toArray();

            if (empty($fcmTokens)) {
                break;
            }

            // Prepare notification data for each token
            $notifications = array_map(function ($token) use ($data) {
                return [
                    'to' => $token,
                    'title' => $data['title'],
                    'body' => $data['body'],
                ];
            }, $fcmTokens);
            
            // Send notifications for this batch
            $notification = new PushNotification();
            $notification->send($notifications);

            $totalSent += count($fcmTokens);
            $counter += 99;
        }

        return response()->json([
            'message' => 'Notifications sent to ' . $totalSent . ' users.'
        ], 200);
    }
}
