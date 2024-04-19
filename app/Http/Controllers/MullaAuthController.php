<?php

namespace App\Http\Controllers;

use App\Jobs\Jobs;
use App\Models\ForgotPasswordTokens;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class MullaAuthController extends Controller
{
    use UniqueId, Reusables;

    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:11',
            'password' => 'required',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'User logged in successfully',
                    'user' => $user,
                    'token' => $user->createToken($request->phone)->plainTextToken
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Incorrect password, try again'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Account not found, please sign up first'
            ], 404);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'phone' => 'required|numeric|digits:11|unique:users,phone',
            'password' => 'required',
            'email' => 'email|unique:users,email',
        ]);

        // Check if user already exists
        if (User::where('phone', $request->phone)->exists()) {
            return response()->json([
                'message' => 'Account with this phone number already exists, please sign in'
            ]);
        }

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email' => $request->email ? $request->email : null,
        ]);

        // Add contact to brevo, maybe dispatch to a job later
        Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => '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',
            'content-type' => 'application/json'
        ])->post('https://api.brevo.com/v3/contacts', [
            "attributes" => [
                "firstname" =>  $request->firstname,
                "lastname" => $request->lastname,
            ],
            "email" => $request->email,
            "updateEnabled" => false
        ]);

        Jobs::dispatch([
            'type' => 1,
            'firstname' => $request->firstname,
            'email' => $request->email ?? 'pikeconcept@gmail.com',
        ]);

        MullaUserWallets::create([
            'user_id' => $user->id,
        ]);

        MullaUserCashbackWallets::create([
            'user_id' => $user->id,
        ]);

        $this->sendToDiscord($user->firstname .', ' . $user->email . ' just created an account!');

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $user->createToken($request->phone)->plainTextToken
        ], 200);
    }

    public function sendToken(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:11'
        ]);

        if (!$user = User::where('phone', $request->phone)->first()) {
            return response([
                'message' => 'Account not found'
            ], 404);
        }

        $fg = ForgotPasswordTokens::create([
            'token' =>  Str::upper($this->uuid_ag2()),
            'email' => $user->email,
            'phone' => $user->phone
        ]);

        Jobs::dispatch([
            'type' => 2,
            'token' => $fg->token,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'Token sent successfully'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required'
        ]);

        $fg = ForgotPasswordTokens::where('token', $request->token)->first();

        if (!$fg) {
            return response()->json([
                'message' => 'Token not found or expired, request a new one.'
            ], 401);
        }

        $user = User::where('email', $fg->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid email'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        $fg->delete();

        Jobs::dispatch([
            'type' => 3,
            'email' => $user->email
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'User logged out successfully'
        ], 200);
    }

    public function getUser(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ], 200);
    }

    public function getUserWallets()
    {
        return response()->json([
            'wallet' => number_format(MullaUserWallets::where('user_id', Auth::id())->sum('balance') + MullaUserCashbackWallets::where('user_id', Auth::id())->sum('balance'), 2),
            'cashback_wallet' => number_format(MullaUserCashbackWallets::where('user_id', Auth::id())->sum('balance'), 2)
        ], 200);
    }
}
