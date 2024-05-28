<?php

namespace App\Http\Controllers;

use App\Jobs\DiscordBots;
use App\Jobs\Jobs;
use App\Models\ForgotPasswordTokens;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Models\VerifyEmailToken;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Carbon\Carbon;
use hisorange\BrowserDetect\Facade as Browser;
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

        $browser = Browser::browserFamily();
        $platform = Browser::platformName();

        $user = User::where('phone', $request->phone)->first();

        if ($user) {
            if (Hash::check($request->password, $user->password)) {

                Jobs::dispatch([
                    'type' => 'login',
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'ip' => request()->ip(),
                    'browser' =>  $browser,
                    'platform' => $platform,
                ]);

                return response()->json([
                    'message' => 'Logged in.',
                    'user' => $user,
                    'token' => $user->createToken($request->phone)->plainTextToken
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

    public function registrationToken(Request $request)
    {
        $request->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'phone' => 'required|numeric|digits:11|unique:users,phone',
            'password' => 'required',
            'email' => 'email|unique:users,email',
        ]);

        $vt = VerifyEmailToken::updateOrCreate([
            'email' => $request->email,
        ], [
            'token' => Str::upper($this->uuid_ag2()),
        ]);

        Jobs::dispatch([
            'type' => 'verify_email',
            'token' => $vt->token,
            'firstname' => $request->firstname,
            'email' => $request->email,
        ]);

        return response(['message' => 'Token sent. Please check your email.'], 200);
    }

    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        // Verify token
        if ($vt = VerifyEmailToken::where('token', $request->token)->where('email', $request->email)->first()) {
            $vt->delete();
        } else {
            return response(['message' => 'Invalid token, please request for a new one.'], 401);
        }

        // Check if user exists
        if (User::where('phone', $request->phone)->exists()) {
            return response()->json([
                'message' => 'Account with this phone number already exists, please sign in.'
            ]);
        }

        // Create user
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email' => $request->email ? $request->email : null,
        ]);

        Jobs::dispatch([
            'type' => 1,
            'user_id' => $user->id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'email' => $request->email ?? 'pikeconcept@gmail.com',
        ]);

        $this->sendToDiscord($user->firstname . ', ' . $user->email . ' just created an account!');

        return response()->json([
            'message' => 'User created successfully.',
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
            'phone' => $user->phone,
        ]);

        Jobs::dispatch([
            'type' => 2,
            'token' => $fg->token,
            'firstname' => $user->firstname,
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

        $browser = Browser::browserFamily();
        $platform = Browser::platformName();

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
            'email' => $user->email,
            'firstname' => $user->firstname,
            'ip' => request()->ip(),
            'browser' =>  $browser,
            'platform' => $platform,
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'User logged out successfully.'
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
            'wallet' => number_format(MullaUserWallets::where('user_id', Auth::id())->sum('balance'), 2),
            'cashback_wallet' => number_format(MullaUserCashbackWallets::where('user_id', Auth::id())->sum('balance'), 2)
        ], 200);
    }
}
