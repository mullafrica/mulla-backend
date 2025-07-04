<?php

namespace App\Http\Controllers;

use App\Enums\SafeHavenEnums;
use App\Jobs\DiscordBots;
use App\Jobs\Jobs;
use App\Models\Alt\BVNVerificationDataModel;
use App\Models\ForgotPasswordTokens;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Models\UserAltBankAccountsModel;
use App\Models\VerifyEmailToken;
use App\Models\VerifyPhoneTokenModel;
use App\Services\ComplianceService;
use App\Services\CustomerIoService;
use App\Services\SafeHavenService;
use App\Services\VirtualAccount;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Carbon\Carbon;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class MullaAuthController extends Controller
{
    use UniqueId, Reusables;

    public function resolveAccount(Request $request, ComplianceService $cs)
    {
        return $cs->resolveAccount([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'account_number' => $request->phone,
        ]);
    }

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

                // Track this signin event
                $cio = new CustomerIoService();
                $cio->trackEvent([
                    'email' => $user->email,
                    'name' => 'last_sign_in',
                    'ip' => request()->ip(),
                    'browser' =>  $browser,
                    'platform' => $platform,
                ], 'last_sign_in');

                $token = $user->createToken($request->phone, ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken;

                // Enhanced Discord logging for successful login
                DiscordBots::dispatch([
                    'message' => '🔒 **User login successful**',
                    'details' => [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'browser' => $browser,
                        'platform' => $platform,
                        'location' => request()->header('CF-IPCountry') ?? 'Unknown',
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);

                return response()->json([
                    'message' => 'Logged in.',
                    'user' => $user,
                    'token' => $token
                ], 200);
            } else {
                // Log failed password attempt
                DiscordBots::dispatch([
                    'message' => '🔒 **Login failed - Wrong password**',
                    'details' => [
                        'phone' => $request->phone,
                        'user_exists' => 'YES',
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'reason' => 'Incorrect password',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'browser' => $browser,
                        'platform' => $platform,
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                
                return response()->json([
                    'message' => 'Incorrect password, try again.'
                ], 400);
            }
        } else {
            // Log failed login attempt for non-existent user
            DiscordBots::dispatch([
                'message' => '🔒 **Login failed - User not found**',
                'details' => [
                    'phone' => $request->phone,
                    'user_exists' => 'NO',
                    'reason' => 'Account not found',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'browser' => $browser,
                    'platform' => $platform,
                    'location' => request()->header('CF-IPCountry') ?? 'Unknown',
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
            
            return response()->json([
                'message' => 'Account not found, please sign up first.'
            ], 400);
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

        // Validate phone number instead

        Jobs::dispatch([
            'type' => 'verify_email',
            'token' => $vt->token,
            'firstname' => $request->firstname,
            'email' => $request->email,
        ]);

        return response(['message' => 'Token sent. Please check your email.'], 200);
    }

    public function register(Request $request, VirtualAccount $va)
    {
        $request->validate([
            'token' => 'required',
        ]);

        $browser = Browser::browserFamily();
        $platform = Browser::platformName();

        // Verify token
        if ($vt = VerifyEmailToken::where('token', $request->token)->where('email', $request->email)->first()) {
            $vt->delete();
        } else {
            return response(['message' => 'Invalid token, please request for a new one.'], 400);
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

        // 2 -> Create Wallet
        MullaUserWallets::updateOrCreate([
            'user_id' => $user->id,
        ]);

        // 3 -> Create Cashback Wallet
        MullaUserCashbackWallets::updateOrCreate([
            'user_id' => $user->id,
        ]);

        // 4 -> Create Paystack Customer
        $pt = $va->createCustomer([
            'user_id' => $user->id,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
        ]);

        // 5 -> Create DVA
        $va->createVirtualAccount($pt, [
            'user_id' => $user->id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
        ]);

        Jobs::dispatch([
            'type' => 'create_account',
            'user_id' => $user->id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'email' => $request->email,
            'created_at' => Carbon::now()->toDateTimeString(),
            'ip' => request()->ip(),
            'browser' =>  $browser,
            'platform' => $platform,
        ]);

        DiscordBots::dispatch([
            'message' => '🎉 **New user registration**',
            'details' => [
                'user_id' => $user->id,
                'name' => $user->firstname . ' ' . $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'ip_address' => request()->ip(),
                'browser' => $browser,
                'platform' => $platform,
                'timestamp' => now()->toDateTimeString()
            ]
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'token' => $user->createToken($request->phone, ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken
        ], 200);
    }

    public function registerWebUpdated(Request $request, VirtualAccount $va)
    {
        $browser = Browser::browserFamily();
        $platform = Browser::platformName();

        $request->validate([
            'token' => 'required',
            'firstname' => 'required',
            'lastname' => 'required',
            'password' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'bvn' => 'required',
            'nuban' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
        ]);

        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email' => $request->email ? $request->email : null,
        ]);

        UserAltBankAccountsModel::updateOrCreate([
            'user_id' => $user->id,
        ], [
            'bvn' => $request->bvn,
            'nuban' => $request->nuban,
            'bank_code' => $request->bank_code,
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
        ]);

        // 2 -> Create Wallet
        MullaUserWallets::updateOrCreate([
            'user_id' => $user->id,
        ]);

        // 3 -> Create Cashback Wallet
        MullaUserCashbackWallets::updateOrCreate([
            'user_id' => $user->id,
        ]);

        // 4 -> Create Paystack Customer
        $pt = $va->createCustomer([
            'user_id' => $user->id,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
        ]);

        Jobs::dispatch([
            'type' => 'validate_bvn',
            'user_id' => $user->id,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'email' => $request->email,
            'created_at' => Carbon::now()->toDateTimeString(),
            'ip' => request()->ip(),
            'browser' =>  $browser,
            'platform' => $platform,
            'pt' => $pt->data->customer_code,
            'nuban' => $request->nuban,
            'bvn' => $request->bvn,
            'bank_code' => $request->bank_code,
        ]);

        DiscordBots::dispatch([
            'message' => '🎉 **New user registration with verification**',
            'details' => [
                'user_id' => $user->id,
                'name' => $user->firstname . ' ' . $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'bvn_provided' => 'Yes',
                'bank_account' => $request->bank_name . ' - ' . $request->nuban,
                'ip_address' => request()->ip(),
                'browser' => $browser,
                'platform' => $platform,
                'timestamp' => now()->toDateTimeString()
            ]
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'token' => $user->createToken($request->phone, ['*'], now()->addMinutes(config('sanctum.expiration')))->plainTextToken
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
            ], 400);
        }

        $user = User::where('email', $fg->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid email'
            ], 400);
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
        return response($request->user(), 200);
    }

    public function getUserWallets()
    {
        return response()->json([
            'wallet' => number_format(MullaUserWallets::where('user_id', Auth::id())->sum('balance'), 2),
            'cashback_wallet' => number_format(MullaUserCashbackWallets::where('user_id', Auth::id())->sum('balance'), 2)
        ], 200);
    }

    public function updateFcm(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        if ($user = User::where('id', Auth::id())->first()) {
            $user->update([
                'fcm_token' => $request->token
            ]);

            return response([
                'message' => 'FCM token updated successfully.'
            ], 200);
        }
    }

    public function sendVerificationCodeToWhatsapp(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:11|unique:users,phone',
            'email' => 'email|unique:users,email'
        ]);

        $token = strtoupper($this->uuid_ag());

        if (str_starts_with($request->phone, '0')) {
            $modifiedNumber = '234' . substr($request->phone, 1);
        } else {
            $modifiedNumber = '234' . $request->phone;
        }

        $res = Http::withHeaders([
            "Authorization" => "App 0e36daa38a3f656b702dfbc5a13f5a73-89b9411a-3a66-4110-8771-3e546a13c2a0",
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ])->post('https://4e3x4m.api.infobip.com/whatsapp/1/message/template', [
            'messages' => [
                [
                    'from'      => '254748067849',
                    'to'        => $modifiedNumber,
                    'messageId' => $this->uuid16(),
                    'content'   => [
                        'templateName' => 'verification_code',
                        'templateData' => [
                            'body' => ['placeholders' => [$token]],
                            'buttons' => [['type' => 'URL', 'parameter' => $token]],
                        ],
                        'language' => 'en_GB',
                    ],
                ],
            ],
        ]);

        if ($res->successful()) {
            VerifyPhoneTokenModel::updateOrCreate([
                'phone' => $request->phone,
            ], [
                'token' => $token,
            ]);

            return response()->json([
                'message' => 'Verification code sent successfully.',
                'token' => $res->json()
            ], 200);
        }

        return response()->json([
            'message' => 'Something went wrong. Please try again later.',
        ], 400);
    }

    public function verifyVerificationCodeFromWhatsapp(Request $request)
    {
        $request->validate([
            'phone' => 'required|numeric|digits:11',
            'token' => 'required|max:6',
        ]);

        if (!$token = VerifyPhoneTokenModel::where('token', $request->token)->where('phone', $request->phone)->first()) {
            return response()->json([
                'message' => 'Invalid verification code.'
            ], 400);
        }

        $token->delete();

        return response()->json([
            'message' => 'Phone verified successfully.'
        ], 200);
    }

    public function resolveBankAccount(Request $request)
    {
        $request->validate([
            'nuban' => 'required|numeric|digits:10',
            'bank_code' => 'required|numeric',
        ]);

        $res = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->get('https://api.paystack.co/bank/resolve?account_number=' . $request->nuban . '&bank_code=' . $request->bank_code);

        if ($res->successful()) {
            return response()->json([
                'data' => $res->json()['data']
            ], 200);
        } else {
            return response()->json([
                'message' => $res->json()['message'],
            ], 400);
        }
    }

    public function sendBVNToken(Request $request, SafeHavenService $sh)
    {
        $request->validate([
            'bvn' => 'required|numeric|digits:11',
        ]);

        $token = $sh->token();

        $res = Http::withToken($token)->post('https://api.safehavenmfb.com/identity/v2', [
            "type" => "BVN",
            "number" => $request->bvn,
            "debitAccountNumber" => SafeHavenEnums::ACCOUNT_NUMBER,
        ]);

        if ($res->successful()) {
            BVNVerificationDataModel::updateOrCreate([
                'bvn' => $request->bvn,
                'bvn_id' => $res->json()['data']['_id'],
            ]);

            return response()->json([
                'message' => 'BVN token sent successfully.',
                '_id' => $res->json()['data']['_id']
            ], 200);
        } else {
            return response()->json([
                'message' => $res->json()['message'],
            ], 400);
        }
    }

    public function ValidateBVNOTP(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        // BVNVerificationDataModel::where('bvn', $request->bvn)->update([
        //     'firstName' => $res->json()['data']['firstName'],
        //     'lastName' => $res->json()['data']['lastName'],
        //     'otherName' => $res->json()['data']['otherName'],
        //     'dateOfBirth' => $res->json()['data']['dateOfBirth'],
        //     'phoneNumber' => $res->json()['data']['phoneNumber'],
        //     'enrollmentBank' => $res->json()['data']['enrollmentBank'],
        //     'enrollmentBranch' => $res->json()['data']['enrollmentBranch'],
        //     'image' => $res->json()['data']['image'],
        // ]);

        return response()->json([
            'message' => 'Token sent successfully'
        ], 200);
    }
}
