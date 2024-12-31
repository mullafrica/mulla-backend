<?php

namespace App\Http\Controllers;

use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserTransactions;
use App\Services\CustomerIoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MullaStatsController extends Controller
{
    public function getStats()
    {
        $user = Auth::user();

        $data = Cache::remember(
            "user_stats_cache" . $user->id,
            60 * 24 * 24,
            function () use ($user) {
                return [
                    "created_at" => Carbon::parse($user->created_at)->isoFormat(
                        "LL"
                    ),
                    "total_cashback" => number_format(
                        $user->cashback_wallet,
                        2
                    ),
                    "total_amount_spent" => number_format(
                        MullaUserTransactions::where("user_id", $user->id)
                            ->where("status", 1)
                            ->whereNot("type", "Bank Transfer")
                            ->sum("amount"),
                        2
                    ),
                    "total_transaction_count" => MullaUserTransactions::where(
                        "user_id",
                        $user->id
                    )
                        ->where("status", 1)
                        ->count(),
                    "spend_by_type" => [
                        "electricity" => number_format(
                            MullaUserTransactions::where("user_id", $user->id)
                                ->where("status", 1)
                                ->where("type", "Electricity Bill")
                                ->sum("amount"),
                            2
                        ),
                        "airtime" => number_format(
                            MullaUserTransactions::where("user_id", $user->id)
                                ->where("status", 1)
                                ->where("type", "Airtime Recharge")
                                ->sum("amount"),
                            2
                        ),
                        "tv" => number_format(
                            MullaUserTransactions::where("user_id", $user->id)
                                ->where("status", 1)
                                ->where("type", "TV Subscription")
                                ->sum("amount"),
                            2
                        ),
                        "internet_data" => number_format(
                            MullaUserTransactions::where("user_id", $user->id)
                                ->where("status", 1)
                                ->where("type", "Data Services")
                                ->sum("amount"),
                            2
                        ),
                    ],
                ];
            }
        );

        return response($data, 200);
    }

    public function getLastFiveTxns()
    {
        $user = Auth::user();
        $txns = MullaUserTransactions::where("user_id", $user->id)
            ->where("type", "Electricity Bill")
            ->orderBy("created_at", "desc")
            ->take(4)
            ->pluck("amount")
            ->unique();
        return response($txns, 200);
    }

    /**
     * 
     * Mulla Bundled 2024
     * 
     */
    public function getTotalAmountSpent(int $userId): float
    {
        return MullaUserTransactions::where('user_id', $userId)
            ->where('status', 1)
            ->whereNotNull('type')
            ->where('type', '!=', 'Deposit')
            ->where('type', '!=', 'Bank Transfer')
            ->whereYear('created_at', 2024)
            ->sum('amount');
    }

    public function getTotalCashbackEarned(int $userId): float
    {
        return MullaUserCashbackWallets::where('user_id', $userId)
            ->sum('balance');
    }

    public function getFeesSaved(int $userId): float
    {
        // Count number of successful electricity transactions and multiply by 100
        return MullaUserTransactions::where('user_id', $userId)
            ->where('status', 1)
            ->where('type', 'Electricity Bill')
            ->whereYear('created_at', 2024)
            ->count() * 100;
    }

    public function getBillSpendBreakdown(int $userId): array
    {
        $transactions = MullaUserTransactions::where('user_id', $userId)
            ->where('status', 1)
            ->whereNotNull('type')
            ->where('type', '!=', 'Bank Transfer')
            ->where('type', '!=', 'Deposit')
            ->select('type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->whereYear('created_at', 2024)
            ->get();

        $totalSpent = $transactions->sum('total_amount');

        return $transactions->mapWithKeys(function ($transaction) use ($totalSpent) {
            $slugKey = Str::slug($transaction->type, '_');
            return [
                $slugKey => [
                    'title' => $transaction->type, // Keep original name for display
                    'amount' => (int)$transaction->total_amount,
                    'percentage' => $totalSpent > 0
                        ? round(($transaction->total_amount / $totalSpent) * 100, 2)
                        : 0
                ]
            ];
        })->toArray();
    }

    public function getTopTransactionType(int $userId): ?string
    {
        return MullaUserTransactions::where('user_id', $userId)
            ->where('status', 1)
            ->select('type', DB::raw('COUNT(*) as type_count'))
            ->groupBy('type')
            ->whereYear('created_at', 2024)
            ->orderByDesc('type_count')
            ->first()
            ?->type;
    }

    public function getHighestSingleTransaction(int $userId): ?array
    {
        $transaction = MullaUserTransactions::where('user_id', $userId)
            ->where('status', 1)
            ->select('type', 'amount', 'created_at')
            ->orderByDesc('amount')
            ->whereYear('created_at', 2024)
            ->first();

        if (!$transaction) {
            return null;
        }

        return [
            'type' => $transaction->type,
            'amount' => (int)$transaction->amount,
            // I want the date in a more readable format ex: Monday, 1st January 2022
            'date' => Carbon::parse($transaction->created_at)->isoFormat('dddd, Do MMMM YYYY')
        ];
    }

    public function getUserSpendRankPercentage(int $userId): float
    {
        // First, get all users' total spend
        $allUsersSpend = MullaUserTransactions::where('status', 1)
            ->select('user_id', DB::raw('SUM(amount) as total_spent'))
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->whereYear('created_at', 2024)
            ->get();

        // Get current user's total spend
        $userSpend = $allUsersSpend->firstWhere('user_id', $userId)?->total_spent ?? 0;

        if ($userSpend === 0 || $allUsersSpend->isEmpty()) {
            return 100.0;
        }

        // Count how many users have spent more
        $usersAbove = $allUsersSpend->where('total_spent', '>', $userSpend)->count();

        // Calculate percentage (lower is better)
        return round(($usersAbove / $allUsersSpend->count()) * 100, 2);
    }

    public function getTopUsersByVolume(int $limit = 10): array
    {
        return MullaUserTransactions::where('status', 1)
            ->whereNotNull('type')
            ->where('type', '!=', 'Deposit')
            ->whereYear('created_at', 2024)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('user_id')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'user_id' => $transaction->user_id,
                    'transaction_count' => $transaction->transaction_count,
                    'total_amount' => $transaction->total_amount,
                    'average_transaction_amount' => $transaction->total_amount / $transaction->transaction_count
                ];
            })
            ->toArray();
    }

    // Optional: Get all metrics at once for efficiency
    public function getAllMetrics()
    {
        $userId = Auth::id();

        return [
            'total_amount_spent' => $this->getTotalAmountSpent($userId),
            'total_cashback_earned' => $this->getTotalCashbackEarned($userId),
            'fees_saved' => $this->getFeesSaved($userId),
            'bill_spend_breakdown' => $this->getBillSpendBreakdown($userId),
            'top_transaction_type' => $this->getTopTransactionType($userId),
            'highest_single_transaction' => $this->getHighestSingleTransaction($userId),
            'spend_rank_percentage' => $this->getUserSpendRankPercentage($userId)
        ];
    }
}
