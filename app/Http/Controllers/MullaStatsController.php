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
                'total_amount_spent' => number_format(MullaUserTransactions::where('user_id', $id)->where('status', 1)->whereNot('type', 'Bank Transfer')->sum('amount'), 2),
                'total_transaction_count' => MullaUserTransactions::where('user_id', $id)->where('status', 1)->count(),
                'spend_by_type' => [
                    'electricity' => number_format(MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Electricity Bill')->sum('amount'), 2),
                    'airtime' => number_format(MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Airtime Recharge')->sum('amount'), 2),
                    'tv' => number_format(MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'TV Subscription')->sum('amount'), 2),
                    'internet_data' => number_format(MullaUserTransactions::where('user_id', $id)->where('status', 1)->where('type', 'Data Services')->sum('amount'), 2),
                ]
            ];
        });

        return response($data, 200);
    }
}
