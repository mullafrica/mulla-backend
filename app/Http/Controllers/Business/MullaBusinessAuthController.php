<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business\MullaBusinessAccountsModel;
use App\Services\VirtualAccount;
use App\Traits\Reusables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MullaBusinessAuthController extends Controller
{
    use Reusables;
    
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = MullaBusinessAccountsModel::where('email', $request->email)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {

                // Jobs::dispatch([
                //     'type' => 'login',
                //     'email' => $user->email,
                //     'firstname' => $user->firstname,
                //     'ip' => request()->ip(),
                //     'browser' =>  $browser,
                //     'platform' => $platform,
                // ]);

                return response()->json([
                    'message' => 'Logged in.',
                    'user' => $user,
                    'token' => $user->createToken($request->email)->plainTextToken
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Incorrect password, try again.'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Account not found, please sign up first.'
            ], 404);
        }
    }

    public function register(Request $request, VirtualAccount $va)
    {
        $request->validate([
            'access_code' => 'required',
            'business_name' => 'required',
            'rc_number' => 'required|unique:mulla_business_accounts_models,rc_number',
            'firstname' => 'required',
            'lastname' => 'required',
            'phone' => 'required|unique:mulla_business_accounts_models,phone',
            'email' => 'required|email|unique:mulla_business_accounts_models,email',
            'password' => 'required|min:8',
        ]);

        if ($request->access_code !== 'mullabiz') {
            return response()->json([
                'message' => 'Access code is incorrect.'
            ], 401);
        }

        $user = MullaBusinessAccountsModel::create([
            'business_name' => $request->business_name,
            'rc_number' => $request->rc_number,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email' => $request->email ? $request->email : null,
        ]);

        // 2 -> Create Wallet
        // MullaUserWallets::updateOrCreate([
        //     'user_id' => $user->id,
        // ]);

        // 3 -> Create Cashback Wallet
        // MullaUserCashbackWallets::updateOrCreate([
        //     'user_id' => $user->id,
        // ]);

        // 4 -> Create Paystack Customer
        // $pt = $va->createCustomer([
        //     'user_id' => $user->id,
        //     'email' => $request->email,
        //     'firstname' => $request->firstname,
        //     'lastname' => $request->lastname,
        //     'phone' => $request->phone,
        // ]);

        // 5 -> Create DVA
        // $va->createVirtualAccount($pt, [
        //     'user_id' => $user->id,
        //     'firstname' => $request->firstname,
        //     'lastname' => $request->lastname,
        //     'phone' => $request->phone,
        // ]);

        // Jobs::dispatch([
        //     'type' => 'create_account',
        //     'user_id' => $user->id,
        //     'firstname' => $request->firstname,
        //     'lastname' => $request->lastname,
        //     'phone' => $request->phone,
        //     'email' => $request->email ?? 'pikeconcept@gmail.com',
        // ]);

        $this->sendToDiscord($user->firstname . ', ' . $user->email . ' just created a business account!');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'token' => $user->createToken($request->email)->plainTextToken
        ], 200);
    }
}
