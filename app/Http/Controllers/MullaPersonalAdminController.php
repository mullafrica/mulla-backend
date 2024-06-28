<?php

namespace App\Http\Controllers;

use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\AssignOp\Mul;

class MullaPersonalAdminController extends Controller
{
    public function getAllUsers(Request $request)
    {
        if (!$request->hasHeader('secret') || $request->header('secret') !== 'aa8add52-a65a-4153-a1cc-88244f47a730') {
            return response('Unauthorized', 401);
        }

        return response(User::orderBy('created_at', 'desc')->get(), 200);
    }

    public function getAllTransactions(Request $request)
    {
        if (!$request->hasHeader('secret') || $request->header('secret') !== 'aa8add52-a65a-4153-a1cc-88244f47a730') {
            return response('Unauthorized', 401);
        }

        return response(MullaUserTransactions::orderBy('created_at', 'desc')->get(), 200);
    }

    public function getAllStats(Request $request)
    {
        if (!$request->hasHeader('secret') || $request->header('secret') !== 'aa8add52-a65a-4153-a1cc-88244f47a730') {
            return response('Unauthorized', 401);
        }

        return response([
            'txns' => [
                'amount' => number_format(MullaUserTransactions::sum('amount'), 2),
                'count' => MullaUserTransactions::count(),
                'monthly_percent_change' => $this->getTransactionPercentChange(),
            ],
            'users' => [
                'count' => User::count(),
                'monthly_percent_change' => $this->getUserPercentChange(),
            ],
            'wallets' => [
                'amount' => MullaUserWallets::sum('balance'),
                'cashback' => MullaUserCashbackWallets::sum('balance')
            ]
        ], 200);
    }

    public function getTransactionPercentChange()
    {
        // Define the current and previous month
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        // Get the total amount for the current month
        $currentMonthTotal = MullaUserTransactions::where('created_at', '>=', $currentMonth)
            ->sum('amount');

        // Get the total amount for the previous month
        $previousMonthTotal = MullaUserTransactions::where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->sum('amount');

        // Calculate the percent change
        if ($previousMonthTotal == 0) {
            // To handle division by zero if there were no transactions in the previous month
            $percentChange = $currentMonthTotal > 0 ? 100 : 0;
        } else {
            $percentChange = (($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal) * 100;
        }

        return $percentChange;
    }

    public function getUserPercentChange()
    {
        // Define the current and previous month
        $currentMonth = Carbon::now()->startOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();

        // Get the total amount for the current month
        $currentMonthTotal = User::where('created_at', '>=', $currentMonth)
            ->count();

        // Get the total amount for the previous month
        $previousMonthTotal = User::where('created_at', '>=', $previousMonth)
            ->where('created_at', '<', $currentMonth)
            ->count();

        // Calculate the percent change
        if ($previousMonthTotal == 0) {
            // To handle division by zero if there were no transactions in the previous month
            $percentChange = $currentMonthTotal > 0 ? 100 : 0;
        } else {
            $percentChange = (($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal) * 100;
        }

        return $percentChange;
    }
}
