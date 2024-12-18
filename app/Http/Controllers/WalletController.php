<?php

namespace App\Http\Controllers;

use App\Enums\BaseUrls;
use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Services\VirtualAccount;
use App\Services\WalletService;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    use UniqueId;

    public function getVirtualAccount(VirtualAccount $va) {        
        if ($dva = CustomerVirtualAccountsModel::where('user_id', Auth::id())->first()) {
            return $dva;
        } else {
            $user = Auth::user();

            $pt = $va->createCustomer([
                'user_id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
            ]);

            $dva = $va->createVirtualAccount($pt, [
                'user_id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
            ]);

            if ($dva) {
                return response(CustomerVirtualAccountsModel::where('user_id', Auth::id())->first(), 200);
            } else {
                return response('An error occured', 400);
            }
        }
    }

    public function payWithWallet(Request $request, WalletService $ws) {
        $request->validate([
            'amount' => 'required|numeric|min:50',
        ]);

        if ($ws->checkBalance($request->amount * BaseUrls::MULTIPLIER)) {

            $m = MullaUserTransactions::create(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $this->uuid(),
                    'amount' => $request->amount,
                    'status' => false
                ]
            );

            return response(['reference' => $m->payment_reference], 200);
        } else {
            return response(['message' => 'Your balance is insufficient, please fund your wallet.', 'status' => false], 200);
        }
    }
}
