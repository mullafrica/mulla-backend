<?php

namespace App\Http\Controllers;

use App\Models\MullaUserTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MullaTransactionsController extends Controller
{
    /**
     * 
     * Transactions
     * 
     * 
     */
    public function storeTxn(Request $request)
    {
        $request->validate([
            'reference' => 'required',
            'amount' => 'required',
        ]);

        MullaUserTransactions::create(
            [
                'user_id' => Auth::id(),
                'payment_reference' => $request->reference,
                'amount' => $request->amount,
                'status' => false
            ]
        );

        return response()->json(['message' => 'Transaction stored successfully'], 200);
    }

    public function getUserTxns()
    {
        return response()->json(
            MullaUserTransactions::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->where('status', true)
                ->orWhere('vtp_status', 2);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(),
            200
        );
    }

    public function getAllUserTxns()
    {
        return response()->json(
            MullaUserTransactions::where('user_id', Auth::id())
                ->where(function ($query) {
                    $query->where('status', true)
                        ->orWhere('vtp_status', 2);
                })
                ->orderBy('created_at', 'desc')
                ->get(),
            200
        );
    }
}
