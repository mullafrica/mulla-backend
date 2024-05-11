<?php

namespace App\Http\Controllers;

use App\Models\CustomerVirtualAccountsModel;
use App\Services\VirtualAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
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
}
