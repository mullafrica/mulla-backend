<?php

namespace App\Http\Controllers;

use App\Models\MullaUserTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MullaStatsController extends Controller
{
    public function getStats()
    {
        $id = Auth::id();

        $data = Cache::remember('user_stats_cache' . $id, 60 * 24 * 24, function () use ($id) {
            return [
                'total_amount_spent' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->whereNot('type', 'Bank Transfer')->sum('amount'),
                'total_transaction_count' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->count(),
                'spend_by_type' => [
                    'electricity' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Electricity Bill')->sum('amount'),
                    'airtime' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Airtime Recharge')->sum('amount'),
                    'tv' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'TV Subscription')->sum('amount'),
                    'internet_data' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Data Services')->sum('amount'),
                ]
            ];
        });

        return response($data, 200);
    }
}
