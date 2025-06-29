<?php

namespace App\Http\Controllers;

use App\Enums\BaseUrls;
use App\Enums\Cashbacks;
use App\Enums\VTPEnums;
use App\Jobs\DiscordBots;
use App\Jobs\Jobs;
use App\Models\MullaUserAirtimeNumbers;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserInternetDataNumbers;
use App\Models\MullaUserMeterNumbers;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserTvCardNumbers;
use App\Models\MullaUserWallets;
use App\Services\WalletService;
use App\Services\SafeHavenService;
use App\Traits\Reusables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class MullaBillController extends Controller
{
    use Reusables;

    public function vtp_endpoint()
    {
        if (env('APP_ENV') === 'production') {
            return "https://vtpass.com/api/";
        }

        return "https://vtpass.com/api/";
    }

    // A
    public function getOperators(Request $request)
    {
        if ($request->has('bill')) {
            // Pass the bill to this endpoint and return the response
            $ops = Http::withToken(env('BLOC_KEY'))->get('https://api.blochq.io/v1/bills/operators?bill=' . $request->bill);

            if ($ops->successful()) {
                return response()->json($ops->json(), 200);
            } else {
                return response()->json($ops->json(), 400);
            }
        } else {
            return response()->json(['error' => 'Bill not provided'], 400);
        }
    }

    // B
    public function getOperatorProducts($operatorId, $bill)
    {
        $pid = Http::withToken(env('BLOC_KEY'))->get('https://api.blochq.io/v1/bills/operators/' . $operatorId . '/products?bill=' . $bill);
        return $pid->json();
    }

    // C
    public function validateMeter(Request $request, $op_id)
    {
        $request->validate([
            'meter_type' => 'required',
            'bill' => 'required',
            'device_number' => 'required'
        ]);

        if ($request->has('meter_type') && $request->has('bill') && $request->has('device_number')) {
            // Pass the meter to this endpoint and return the response
            $validate = Http::withToken(env('BLOC_KEY'))->withOptions([
                'timeout' => 120,
            ])->get('https://api.blochq.io/v1/bills/customer/validate/' . $op_id, [
                'bill' => $request->bill,
                'meter_type' => $request->meter_type,
                'device_number' => $request->device_number
            ]);

            if ($validate->successful()) {
                // Fetch and store data
                $device = $validate->object();

                // Store meter for user
                MullaUserMeterNumbers::updateOrCreate([
                    'meter_number' => $request->device_number,
                    'user_id' => Auth::id(),
                ], [
                    'user_id' => Auth::id(),
                    'name' => $device->data->name,
                    'meter_type' => $request->meter_type,
                    'address' => $device->data->address
                ]);
                return response()->json($validate->json(), 200);
            } else {
                return response()->json($validate->json(), 400);
            }
        } else {
            return response()->json(['error' => 'Meter or a required parameter not provided'], 400);
        }
    }

    public function getUserMeters()
    {
        $value = MullaUserMeterNumbers::where('user_id', Auth::id())->get();
        return response()->json($value, 200);
    }

    public function getUserTvCardNumbers()
    {
        $value = MullaUserTvCardNumbers::where('user_id', Auth::id())->get();
        return response()->json($value, 200);
    }

    public function getUserAirtimeNumbers()
    {
        $value = MullaUserAirtimeNumbers::where('user_id', Auth::id())->get();
        return response()->json($value, 200);
    }

    public function getUserInternetDataNumbers()
    {
        $value = MullaUserInternetDataNumbers::where('user_id', Auth::id())->get();
        return response()->json($value, 200);
    }

    public function payABill(Request $request)
    {
        $request->validate([
            'bill' => 'required',
            'email' => 'required',
            'operator_id' => 'required',
            'amount' => 'required'
        ]);

        // if amount negative
        if ($request->amount < 0) {
            return response()->json(['error' => 'Amount cannot be negative'], 400);
        }

        if ($request->bill == 'electricity') {
            $request->validate([
                'meter_type' => 'required',
                'device_number' => 'required',
            ]);
        }

        // Get product id if the bill is electricity
        $op_id = $this->getOperatorProducts($request->operator_id, $request->bill);
        if ($op_id['success'] === true) {
            $product_id = $op_id['data'][0]['id'];
        } else {
            return response()->json($op_id, 400);
        }

        $data = [
            "device_details" => [
                "meter_type" => $request->meter_type,
                "device_number" => $request->device_number,
                "beneficiary_msisdn" => $request->beneficiary_msisdn
            ],

            "meta_data" => [
                "email" => $request->email,
            ],

            "operator_id" => $request->operator_id,
            "product_id" => $product_id,
            "amount" => $request->amount * 100,
        ];

        // Pass the bill to this endpoint and return the response
        $pay = Http::withToken(env('BLOC_KEY'))->withOptions([
            'timeout' => 120,
        ])->post('https://api.blochq.io/v1/bills/payment?bill=' . $request->bill, $data);

        if ($pay->successful()) {
            /**
             * TODO: Implement cashback functionality
             * I am capping all cashback to 1.5% for now
             * Credit wallet with 1.5% cashback
             */

            // Credit cashback wallet with 1.5% cashback
            MullaUserCashbackWallets::updateOrCreate(['user_id' => Auth::id()])
                ->increment('balance', $request->amount * 0.015);

            return response()->json(['res' => $pay->json(), 'message' => 'Payment successful'], 200);
        } else {
            return response()->json(['res' => $pay->json(), 'message' => 'Something went wrong, try again later.'], 400);
        }

        return $pay->json();
    }

    /** 
     * 
     * 
     * 
     * 
     * VTPASS
     * 
     * 
     * 
     * 
     */
    public function getVTPassOperatorProducts(Request $request)
    {
        if ($request->bill === 'electricity') {
            $identifier = 'electricity-bill';
        }

        if ($request->bill === 'tv') {
            $identifier = 'tv-subscription';
        }

        if ($request->bill === 'airtime') {
            $identifier = 'airtime';
        }

        if ($request->bill === 'data') {
            $identifier = 'data';
        }

        if ($identifier) {
            $response = Cache::remember('operator_products_' . $identifier, 60 * 60 * 24, function () use ($identifier) {
                $ops = Http::withHeaders([
                    'api-key' => env('VTPASS_API_KEY'),
                    'public-key' => env('VTPASS_PUB_KEY')
                ])->get($this->vtp_endpoint() . 'services?identifier=' . $identifier);

                $data = $ops->json();

                $mappedContent = array_map(function ($service) {
                    $service['id'] = $service['serviceID']; // Create a new 'id' property
                    unset($service['serviceID']); // Remove the original 'serviceID' property
                    return $service;
                }, $data['content']);

                // Filter out foreign-airtime
                $mappedContent = array_filter($mappedContent, function ($service) {
                    return $service['id'] !== 'foreign-airtime';
                });

                $response['data'] = array_values($mappedContent);

                return $response;
            });

            return $response;
        } else {
            return response()->json(['error' => 'Bill identifier not provided'], 400);
        }
    }

    public function getVTPassOperatorProductVariation(Request $request)
    {
        if ($request->id) {
            $ops = Cache::remember('operator_variation_' . $request->id, 60 * 60 * 24, function () use ($request) {
                $ops = Http::withHeaders([
                    'api-key' => env('VTPASS_API_KEY'),
                    'public-key' => env('VTPASS_PUB_KEY')
                ])->get($this->vtp_endpoint() . 'service-variations?serviceID=' . $request->id);

                return $ops->object()->content->variations;
            });

            return $ops;
        }
    }

    public function validateVTPassMeter(Request $request, $op_id)
    {
        $request->validate([
            'meter_type' => 'required',
            'bill' => 'required',
            'device_number' => 'required'
        ]);

        if ($request->has('meter_type') && $request->has('bill') && $request->has('device_number')) {
            // First attempt: Try VTPass validation
            $validate = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'secret-key' => env('VTPASS_SEC_KEY')
            ])->withOptions([
                'timeout' => 120,
            ])->post($this->vtp_endpoint() . 'merchant-verify?billersCode=' . $request->device_number . '&serviceID=' . $op_id . '&type=' . $request->meter_type);

            $data = $validate->object();

            // Check if VTPass validation was successful
            if (!isset($data->content->error) && isset($validate) && $validate->status() === 200 && !($data->content->WrongBillersCode ?? false)) {
                // VTPass validation successful
                $device = $validate->object();


                // Check if the meter number has two duplicates and delete only the first one
                if (MullaUserMeterNumbers::where('meter_number', $request->device_number)->count() > 1) {
                    MullaUserMeterNumbers::where('meter_number', $request->device_number)->first()->delete();
                }

                // Store meter for user with VTPass as validation provider
                MullaUserMeterNumbers::updateOrCreate([
                    'meter_number' => $request->device_number,
                    'user_id' => Auth::id(),
                ], [
                    'address' => $device->content->Address ?? '',
                    'name' => $device->content->Customer_Name ?? '',
                    'meter_type' => $device->content->Meter_Type ?? $request->meter_type,
                    'disco' => $op_id ?? '',
                    'validation_provider' => 'vtpass'
                ]);

                return response()->json([
                    'data' => [
                        "address" => $device->content->Address ?? '',
                        "name" => $device->content->Customer_Name ?? '',
                        "validation_provider" => "vtpass"
                    ]
                ], 200);
            } 
            // VTPass validation failed - attempt SafeHaven fallback for electricity only
            else if (isset($data->content->WrongBillersCode) && $data->content->WrongBillersCode === true) {
                return $this->attemptSafeHavenMeterValidation($request);
            } else {
                // Other VTPass errors (not meter validation specific)
                return response()->json($validate->json(), 400);
            }
        } else {
            return response()->json(['error' => 'Meter or a required parameter not provided'], 400);
        }
    }

    /**
     * Attempt SafeHaven meter validation as fallback when VTPass fails
     */
    private function attemptSafeHavenMeterValidation(Request $request)
    {
        try {
            // Get SafeHaven access token
            $tokenResponse = Http::post('https://api.safehavenmfb.com/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => env('SAFE_HAVEN_CLIENT_ID', '7e36708c22981a084fff541768f0a33e'),
                'client_assertion' => env('SAFE_HAVEN_CLIENT_ASSERTION', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL211bGxhLm1vbmV5Iiwic3ViIjoiN2UzNjcwOGMyMjk4MWEwODRmZmY1NDE3NjhmMGEzM2UiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzM5OTU4MzQyLCJleHAiOjE3NzE0OTM2NDV9.JEyVWS82VscoErhhuJ2MW9qAnWWuHFsX168_Q6o0HjJR4xDaXIEm7tSEbkbvc-x-cnM9AYi30LQqyI24nFxvvY2rESGu6uG2BA0eIct-0HJHpG9Qr39ff8T_e107okPL5zMfFyPDtfaLSxAxJEWPk7moJD0pNprjF7PP6LrGdaY'),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
            ]);
            
            if (!$tokenResponse->successful() || !isset($tokenResponse->json()['access_token'])) {
                throw new \Exception('Failed to get SafeHaven access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];
            
            // Make SafeHaven meter validation request
            $safeHavenResponse = Http::withToken($accessToken)
                ->withOptions(['timeout' => 120])
                ->post('https://api.safehavenmfb.com/vas/verify', [
                    'serviceCategoryId' => '61efac35da92348f9dde5f77',
                    'entityNumber' => $request->device_number
                ]);

            if ($safeHavenResponse->successful()) {
                $safeHavenData = $safeHavenResponse->json();
                
                // Check if SafeHaven validation was successful
                if (isset($safeHavenData['statusCode']) && $safeHavenData['statusCode'] === 200 && isset($safeHavenData['data'])) {
                    // Check if the meter number has duplicates and clean up
                    if (MullaUserMeterNumbers::where('meter_number', $request->device_number)->count() > 1) {
                        MullaUserMeterNumbers::where('meter_number', $request->device_number)->first()->delete();
                    }

                    // Store meter for user with SafeHaven validation
                    MullaUserMeterNumbers::updateOrCreate([
                        'meter_number' => $request->device_number,
                        'user_id' => Auth::id(),
                    ], [
                        'address' => $safeHavenData['data']['address'] ?? '',
                        'name' => $safeHavenData['data']['name'] ?? '',
                        'meter_type' => $safeHavenData['data']['vendType'] ?? $request->meter_type,
                        'disco' => $safeHavenData['data']['discoCode'] ?? '',
                        'validation_provider' => 'safehaven'
                    ]);
                    
                    return response()->json([
                        'data' => [
                            "address" => $safeHavenData['data']['address'] ?? '',
                            "name" => $safeHavenData['data']['name'] ?? '',
                            "validation_provider" => "safehaven"
                        ]
                    ], 200);
                } else {
                    throw new \Exception('SafeHaven validation failed: ' . ($safeHavenData['message'] ?? 'Unknown error'));
                }
            } else {
                throw new \Exception('SafeHaven API request failed: ' . $safeHavenResponse->body());
            }
            
        } catch (\Exception $e) {
            // Both VTPass and SafeHaven failed
            DiscordBots::dispatch([
                'message' => '❌ **Meter validation failed** - Both services unavailable',
                'details' => [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'meter_number' => $request->device_number,
                    'safehaven_error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
            
            return response()->json([
                'error' => 'Unable to validate meter number with any service. Please check the meter number and try again.',
                'validation_failed' => true
            ], 400);
        }
    }

    public function validateSmartCardNumber(Request $request, $op_id)
    {
        $request->validate([
            'service_id' => 'required',
            'device_number' => 'required'
        ]);

        $validate = Http::withHeaders([
            'api-key' => env('VTPASS_API_KEY'),
            'secret-key' => env('VTPASS_SEC_KEY')
        ])->withOptions([
            'timeout' => 120,
        ])->post($this->vtp_endpoint() . 'merchant-verify?billersCode=' . $request->device_number . '&serviceID=' . $request->service_id);

        $data = $validate->object();

        if (!isset($data->content->error)) {
            $device = $validate->object();

            MullaUserTvCardNumbers::updateOrCreate([
                'card_number' => $request->device_number,
                'user_id' => Auth::id(),
            ], [
                'name' => $device->content->Customer_Name,
                'type' => $device->content->Customer_Type,
            ]);

            return response()->json([
                "name" => $device->content->Customer_Name,
                "card_number" =>
                $request->device_number,
            ], 200);
        } else {
            return response()->json(['error' => $data->content->error ?? 'An error occured.'], 400);
        }
    }

    public function payVTPassBill(Request $request, WalletService $ws)
    {
        $request_id = $this->generateRequestId();
        
        // Check for suspicious activity - rapid payment attempts
        $recentPayments = MullaUserTransactions::where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
            
        if ($recentPayments >= 10) {
            DiscordBots::dispatch([
                'message' => '🚨 **Suspicious activity detected** - Rapid payment attempts',
                'details' => [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'count' => $recentPayments,
                    'timeframe' => '5 minutes',
                    'current_attempt' => $request->serviceID . ' - ₦' . number_format($request->amount),
                    'ip_address' => request()->ip(),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        }
        
        // Check for repeated failed attempts on same payment reference
        $failedAttempts = MullaUserTransactions::where('user_id', Auth::id())
            ->where('payment_reference', $request->payment_reference)
            ->where('status', false)
            ->count();
            
        if ($failedAttempts >= 3) {
            DiscordBots::dispatch([
                'message' => '🔄 **Repeated failed attempts** - Same payment reference',
                'details' => [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'payment_reference' => $request->payment_reference,
                    'failed_attempts' => $failedAttempts,
                    'service' => $request->serviceID,
                    'amount' => '₦' . number_format($request->amount),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        }

        $request->validate([
            'payment_reference' => 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'serviceID' => 'required|string|max:100',
            'billersCode' => 'required|string|max:50|regex:/^[0-9a-zA-Z]+$/',
            'amount' => 'required|numeric|min:1|max:1000000',
            'fromWallet' => 'required|boolean'
        ]);

        $amount = floatval($request->amount);

        /** Enhanced amount validation */
        if (!is_numeric($amount) || $amount <= 0 || $amount != $request->amount) {
            return response()->json(['message' => 'Invalid amount provided. Amount must be a positive number.'], 400);
        }

        /** Prevent negative amounts and floating point manipulation */
        if ($amount < 0 || !is_finite($amount) || is_nan($amount)) {
            return response()->json(['message' => 'Invalid amount format.'], 400);
        }

        /** Check if amount is greater than 500 for electricity */
        if ($this->isElectricity($request->serviceID) && $amount < 1000) {
            return response()->json(['message' => 'Minimum amount for electricity is 1000.'], 400);
        }

        /** Check for existing transactions with this reference */
        $existingTxn = MullaUserTransactions::where('payment_reference', $request->payment_reference)->first();
        
        if ($existingTxn && $existingTxn->status == true) {
            return response(['message' => 'Transaction already completed successfully.'], 400);
        }

        /** For electricity: Check if this is a retry after VTPass failure */
        if ($this->isElectricity($request->serviceID) && $existingTxn && 
            $existingTxn->vtp_status === VTPEnums::FAILED && 
            $existingTxn->provider === 'vtpass') {
            // This is a retry after VTPass failed - attempt SafeHaven directly
            return $this->attemptSafeHavenElectricity($request, $ws, $existingTxn);
        }

        /** Create pending transaction record for new attempts */
        if (!$existingTxn) {
            $pendingTxn = MullaUserTransactions::create([
                'user_id' => Auth::id(),
                'payment_reference' => $request->payment_reference,
                'amount' => $amount,
                'status' => false,
                'vtp_status' => VTPEnums::PENDING,
                'provider' => 'vtpass',
                'created_at' => now()
            ]);
        } else {
            $pendingTxn = $existingTxn;
        }

        /** Validate variation code if airtime or data */
        if (!$this->isAirtime($request->serviceID)) {
            $request->validate([
                'variation_code' => 'required'
            ]);
        }

        /** Validate recipient if airtime or data */
        if ($this->isAirtime($request->serviceID)) {
            $request->validate([
                'recipient' => 'required|digits:11',
            ]);

            /** Check if recipient is 11 digits */
            if (strlen($request->recipient) !== 11) {
                return response()->json(['message' => 'Invalid recipient number.'], 400);
            }

            /** Check if amount is greater than 50 */
            if ($amount <= 50) {
                return response()->json(['message' => 'Minimum amount for airtime is 50.'], 400);
            }

            MullaUserAirtimeNumbers::updateOrCreate([
                'phone_number' => $request->recipient,
                'user_id' => Auth::id(),
            ], [
                'telco' => $request->serviceID,
            ]);
        }

        /** Validate recipient if data */
        if ($this->isData($request->serviceID)) {
            $request->validate([
                'recipient' => 'required',
            ]);

            MullaUserInternetDataNumbers::updateOrCreate([
                'phone_number' => $request->recipient,
                'user_id' => Auth::id(),
            ], [
                'telco' => $request->serviceID,
            ]);
        }

        /** Validate meter number if showmax */
        if ($request->serviceID === 'showmax') {
            MullaUserMeterNumbers::updateOrCreate([
                'meter_number' => $request->billersCode,
                'user_id' => Auth::id(),
            ], []);
        }

        $phone = Auth::user()->phone;

        /** For electricity: Check validation provider and route accordingly */
        if ($this->isElectricity($request->serviceID)) {
            // Check which service validated this meter to determine routing
            $meterRecord = MullaUserMeterNumbers::where('meter_number', $request->billersCode)
                ->where('user_id', Auth::id())
                ->first();
                
            if ($meterRecord && $meterRecord->validation_provider === 'safehaven') {
                // Meter was validated via SafeHaven, so purchase directly via SafeHaven
                // But first, deduct wallet balance
                if ($request->fromWallet === true || $request->fromWallet === 'true') {
                    $userWallet = MullaUserWallets::where('user_id', Auth::id())->lockForUpdate()->first();
                    
                    if (!$userWallet || $userWallet->balance < $amount) {
                        if ($pendingTxn && !$existingTxn) {
                            $pendingTxn->delete();
                        }
                        
                        DiscordBots::dispatch([
                            'message' => '💰 **Insufficient balance** - SafeHaven direct payment blocked',
                            'details' => [
                                'user_id' => Auth::id(),
                                'email' => Auth::user()->email,
                                'service' => $request->serviceID,
                                'requested_amount' => '₦' . number_format($amount),
                                'wallet_balance' => '₦' . number_format($userWallet->balance ?? 0),
                                'timestamp' => now()->toDateTimeString()
                            ]
                        ]);
                        
                        return response(['message' => 'Insufficient wallet balance.'], 400);
                    }
                    
                    // Deduct amount for SafeHaven direct payment
                    $userWallet->decrement('balance', $amount);
                }
                
                DiscordBots::dispatch([
                    'message' => '🔀 **Routing to SafeHaven** - Meter validated via SafeHaven',
                    'details' => [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'payment_ref' => $request->payment_reference,
                        'service' => $request->serviceID,
                        'amount' => '₦' . number_format($amount),
                        'meter' => $request->billersCode,
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                
                return $this->attemptSafeHavenElectricity($request, $ws, $pendingTxn);
            }
            // If validation_provider is 'vtpass' or null, continue with VTPass first (existing logic)
        }

        /** Enhanced wallet balance validation and transaction locking */
        if ($request->fromWallet === true || $request->fromWallet === 'true') {
            // For new transactions (not retries), check and deduct wallet balance
            if (!$existingTxn || $existingTxn->vtp_status !== VTPEnums::FAILED) {
                // Lock user wallet for this transaction
                $userWallet = MullaUserWallets::where('user_id', Auth::id())->lockForUpdate()->first();
                
                if (!$userWallet || $userWallet->balance < $amount) {
                    if ($pendingTxn && !$existingTxn) {
                        $pendingTxn->delete(); // Remove pending transaction
                    }
                    
                    // Log insufficient balance
                    DiscordBots::dispatch([
                        'message' => '💰 **Insufficient balance** - Payment blocked',
                        'details' => [
                            'user_id' => Auth::id(),
                            'email' => Auth::user()->email,
                            'service' => $request->serviceID,
                            'requested_amount' => '₦' . number_format($amount),
                            'wallet_balance' => '₦' . number_format($userWallet->balance ?? 0),
                            'shortage' => '₦' . number_format($amount - ($userWallet->balance ?? 0)),
                            'timestamp' => now()->toDateTimeString()
                        ]
                    ]);
                    
                    return response(['message' => 'Insufficient wallet balance.'], 400);
                }
                
                // Deduct amount immediately to prevent double spending
                $userWallet->decrement('balance', $amount);
                
            }
            // For retries, balance was already deducted on first attempt, so skip this check
        }

        /** Pay airtime or data */
        if ($this->isAirtime($request->serviceID)) {
            $pay = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'secret-key' => env('VTPASS_SEC_KEY')
            ])->withOptions([
                'timeout' => 120,
            ])->post($this->vtp_endpoint() . 'pay?request_id=' . $request_id . '&serviceID=' . $request->serviceID . '&amount=' . $amount . '&phone=' . $request->recipient);
        } elseif ($this->isData($request->serviceID)) {
            $pay = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'secret-key' => env('VTPASS_SEC_KEY')
            ])->withOptions([
                'timeout' => 120,
            ])->post($this->vtp_endpoint() . 'pay?request_id=' . $request_id . '&serviceID=' . $request->serviceID . '&billersCode=' . $request->billersCode . '&variation_code=' . $request->variation_code . '&amount=' . $amount . '&phone=' . $request->recipient);
        } else {
            $pay = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'secret-key' => env('VTPASS_SEC_KEY')
            ])->withOptions([
                'timeout' => 120,
            ])->post($this->vtp_endpoint() . 'pay?request_id=' . $request_id . '&serviceID=' . $request->serviceID . '&billersCode=' . $request->billersCode . '&variation_code=' . $request->variation_code . '&amount=' . $amount . '&phone=' . $phone);
        }

        $res = $pay->object();

        /** Check if payment was successful */
        if (isset($res->code) && !in_array($res->code, ['000', '099'])) {
            
            if ($this->isElectricity($request->serviceID)) {
                // For electricity: Automatically attempt SafeHaven when VTPass fails
                
                $errorCode = $res->code ?? 'N/A';
                $errorMessage = $res->response_description ?? 'Unknown error';
                
                $pendingTxn->update([
                    'vtp_status' => VTPEnums::FAILED,
                    'status' => false,
                    'provider' => 'vtpass',
                    'notes' => 'VTPass failed, automatically attempting SafeHaven.',
                    'bill_reference' => $res->requestId ?? 'vtpass_failed_' . time()
                ]);
                
                DiscordBots::dispatch([
                    'message' => '⚡ **VTPass failed** - Auto-attempting SafeHaven',
                    'details' => [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'payment_ref' => $request->payment_reference,
                        'service' => $request->serviceID,
                        'amount' => '₦' . number_format($amount),
                        'meter' => $request->billersCode,
                        'vtpass_error' => $errorMessage,
                        'vtpass_code' => $errorCode,
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                
                // Automatically attempt SafeHaven without user intervention
                return $this->attemptSafeHavenElectricity($request, $ws, $pendingTxn);
                
            } else {
                // For non-electricity services: Refund immediately (original behavior)
                if ($request->fromWallet === true || $request->fromWallet === 'true') {
                    MullaUserWallets::where('user_id', Auth::id())->increment('balance', $amount);
                }
                
                $pendingTxn->delete(); // Remove pending transaction
                DiscordBots::dispatch([
                    'message' => '❌ **Payment failed** - User refunded',
                    'details' => [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'service' => $request->serviceID,
                        'amount' => '₦' . number_format($amount),
                        'recipient' => $request->recipient ?? $request->billersCode,
                        'vtpass_error' => $res->response_description ?? 'Unknown error',
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                return response(['message' => 'Service temporarily unavailable. Please try again later.'], 400);
            }
        }

        /** Log the response to discord */
        // Log successful VTPass transactions
        DiscordBots::dispatch([
            'message' => '✅ **Payment successful** - VTPass',
            'details' => [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'service' => $request->serviceID,
                'amount' => '₦' . number_format($amount),
                'cashback' => '₦' . number_format($amount * $this->cashBack($request->serviceID)),
                'recipient' => $request->recipient ?? $request->billersCode,
                'transaction_id' => $res->requestId ?? 'N/A',
                'timestamp' => now()->toDateTimeString()
            ]
        ]);

        /** Update cashback & wallet balance */
        if (isset($res->response_description) && $res->response_description === 'TRANSACTION SUCCESSFUL') {
            MullaUserCashbackWallets::updateOrCreate(['user_id' => Auth::id()])
                ->increment('balance', $amount * $this->cashBack($request->serviceID));

            MullaUserWallets::updateOrCreate(['user_id' => Auth::id()])
                ->increment('balance', $amount * $this->cashBack($request->serviceID));

            $txn = MullaUserTransactions::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $request->payment_reference
                ],
                [
                    'bill_reference' => $res->content->transactions->transactionId,

                    'unique_element' => $res->content->transactions->unique_element ?? '',
                    'product_name' => $res->content->transactions->product_name ?? '',

                    'cashback' => $amount * $this->cashBack($request->serviceID),
                    'amount' => $amount,
                    'vat' => $res->Tax ?? $res->mainTokenTax ?? 0,
                    'bill_token' => $res->Reference ?? $res->token ?? $res->Token ?? $res->mainToken ?? '',
                    'bill_units' => $res->Units ?? $res->units ?? $res->mainTokenUnits ?? '',
                    'bill_device_id' => $res->content->transactions->unique_element ?? '',
                    'type' => $res->content->transactions->type ?? '',
                    'voucher_code' => $res->cards[0]->pin ?? '',
                    'voucher_serial' => $res->cards[0]->serialNumber ?? '',
                    'vtp_request_id' => $res->requestId,
                    'vtp_status' => VTPEnums::SUCCESS,
                    'status' => true,
                ]
            );

            Jobs::dispatch([
                'type' => 'transaction_successful',
                'email' => Auth::user()->email,
                'firstname' => Auth::user()->firstname,
                'transfer' => '',
                'utility' => $txn->product_name,
                'amount' => $txn->amount,
                'date' => $txn->date ?? now()->format('D dS M \a\t h:i A'),
                'cashback' => $txn->cashback,
                'code' => $txn->voucher_code ?? '',
                'serial' => '',
                'units'  => $txn->bill_units ?? '',
                'device_id' => $txn->bill_device_id ?? '',
                'token' => $txn->bill_token ?? '',
                'txn_type' => $txn->type ?? '',
                'transaction_reference' => $txn->payment_reference,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]);

            return response()->json($txn, 200);
        } else if ($res->content->transactions->status === 'pending' || $res->response_description === 'TRANSACTION PROCESSING - PENDING') {

            // if ($this->isAirtime($request->serviceID)) return;
            // if ($this->isData($request->serviceID)) return;

            $txn = MullaUserTransactions::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $request->payment_reference
                ],
                [
                    'bill_reference' => $res->content->transactions->transactionId,
                    'cashback' => $amount * $this->cashBack($request->serviceID),
                    'amount' => $amount,
                    'bill_device_id' => $res->content->transactions->unique_element,
                    'type' => $res->content->transactions->type,
                    'status' => false,
                    'vtp_request_id' => $res->requestId,
                    'vtp_status' => VTPEnums::PENDING,
                ]
            );

            return response()->json([
                'message' => 'We are processing your transaction, please wait a moment.', 
                'pending' => true,
                'transaction_id' => $txn->id
            ], 200);
        } else {
            /**
             * We are assuming the transaction failed here, nothing
             * should be getting stored.
             */
            return response()->json(['message' => 'Service not available at the moment, please try again later.'], 400);
        }

        return response()->json(['message' => 'An error occured, please contact support.'], 400);
    }

    /**
     * Attempt SafeHaven electricity payment
     * Called either:
     * 1. As fallback when VTPass fails (wallet already deducted)
     * 2. Direct routing when meter was validated via SafeHaven (wallet already deducted)
     */
    private function attemptSafeHavenElectricity(Request $request, WalletService $ws, $pendingTxn)
    {
        try {
            // Note: Wallet balance was already deducted before calling this method
            
            // Get SafeHaven access token
            $tokenResponse = Http::post('https://api.safehavenmfb.com/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => env('SAFE_HAVEN_CLIENT_ID', '7e36708c22981a084fff541768f0a33e'),
                'client_assertion' => env('SAFE_HAVEN_CLIENT_ASSERTION', 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL211bGxhLm1vbmV5Iiwic3ViIjoiN2UzNjcwOGMyMjk4MWEwODRmZmY1NDE3NjhmMGEzM2UiLCJhdWQiOiJodHRwczovL2FwaS5zYWZlaGF2ZW5tZmIuY29tIiwiaWF0IjoxNzM5OTU4MzQyLCJleHAiOjE3NzE0OTM2NDV9.JEyVWS82VscoErhhuJ2MW9qAnWWuHFsX168_Q6o0HjJR4xDaXIEm7tSEbkbvc-x-cnM9AYi30LQqyI24nFxvvY2rESGu6uG2BA0eIct-0HJHpG9Qr39ff8T_e107okPL5zMfFyPDtfaLSxAxJEWPk7moJD0pNprjF7PP6LrGdaY'),
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
            ]);
            
            if (!$tokenResponse->successful() || !isset($tokenResponse->json()['access_token'])) {
                throw new \Exception('Failed to get SafeHaven access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];
            
            // Determine meter type for SafeHaven
            $vendType = strtoupper($request->meter_type ?? 'PREPAID');
            
            $paymentData = [
                'serviceCategoryId' => '61efac35da92348f9dde5f77',
                'amount' => intval($request->amount), // SafeHaven expects amount in Naira, not kobo
                'channel' => 'WEB',
                'debitAccountNumber' => env('SAFE_HAVEN_DEBIT_ACCOUNT', '0111124637'),
                'meterNumber' => $request->billersCode,
                'vendType' => $vendType
            ];
            
            // Make SafeHaven electricity payment
            $safeHavenResponse = Http::withToken($accessToken)
                ->withOptions(['timeout' => 120])
                ->post('https://api.safehavenmfb.com/vas/pay/utility', $paymentData);

            if ($safeHavenResponse->successful()) {
                $safeHavenData = $safeHavenResponse->json();
                
                // Check if SafeHaven transaction was actually successful
                if (isset($safeHavenData['data']['status']) && $safeHavenData['data']['status'] === 'successful') {
                    // Update transaction with SafeHaven success
                    $txn = MullaUserTransactions::updateOrCreate(
                        [
                            'user_id' => Auth::id(),
                            'payment_reference' => $request->payment_reference
                        ],
                        [
                            'bill_reference' => $safeHavenData['data']['reference'] ?? 'SH-' . time(),
                            'amount' => $request->amount,
                            'cashback' => $request->amount * $this->cashBack($request->serviceID),
                            'bill_token' => $safeHavenData['data']['utilityToken'] ?? '',
                            'bill_units' => $safeHavenData['data']['metaData']['units'] ?? '',
                            'vat' => $safeHavenData['data']['metaData']['tax'] ?? 0,
                            'bill_device_id' => $request->billersCode,
                            'type' => 'Electricity Bill',
                            'product_name' => ($safeHavenData['data']['metaData']['disco'] ?? 'Unknown') . ' Electricity',
                            'unique_element' => $request->billersCode,
                            'vtp_request_id' => $safeHavenData['data']['id'] ?? null,
                            'vtp_status' => VTPEnums::SUCCESS,
                            'status' => true,
                            'provider' => 'safehaven',
                            'notes' => 'Processed via SafeHaven fallback after VTPass failure. Receipt: ' . ($safeHavenData['data']['metaData']['receiptNo'] ?? 'N/A')
                        ]
                    );

                // Credit cashback
                MullaUserCashbackWallets::updateOrCreate(['user_id' => Auth::id()])
                    ->increment('balance', $request->amount * $this->cashBack($request->serviceID));

                MullaUserWallets::updateOrCreate(['user_id' => Auth::id()])
                    ->increment('balance', $request->amount * $this->cashBack($request->serviceID));

                // Send success email
                Jobs::dispatch([
                    'type' => 'transaction_successful',
                    'email' => Auth::user()->email,
                    'firstname' => Auth::user()->firstname,
                    'transfer' => '',
                    'utility' => 'Electricity Bill',
                    'amount' => $txn->amount,
                    'date' => $txn->date ?? now()->format('D dS M \a\t h:i A'),
                    'cashback' => $txn->cashback,
                    'code' => $txn->voucher_code ?? '',
                    'serial' => '',
                    'units' => $txn->bill_units ?? '',
                    'device_id' => $txn->bill_device_id ?? '',
                    'token' => $txn->bill_token ?? '',
                    'txn_type' => $txn->type ?? 'electricity',
                    'transaction_reference' => $txn->payment_reference,
                    'created_at' => Carbon::now()->toDateTimeString(),
                ]);

                DiscordBots::dispatch([
                    'message' => '✅ **Payment successful** - SafeHaven fallback',
                    'details' => [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'payment_ref' => $request->payment_reference,
                        'service' => $request->serviceID,
                        'amount' => '₦' . number_format($request->amount),
                        'cashback' => '₦' . number_format($request->amount * $this->cashBack($request->serviceID)),
                        'meter' => $request->billersCode,
                        'token' => $safeHavenData['data']['utilityToken'] ?? 'N/A',
                        'units' => $safeHavenData['data']['metaData']['units'] ?? 'N/A',
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                
                    return response()->json([
                        'message' => 'Payment successful via SafeHaven',
                        'data' => $txn,
                        'provider' => 'safehaven'
                    ], 200);
                } else if (isset($safeHavenData['data']['status']) && $safeHavenData['data']['status'] === 'pending') {
                    // Handle SafeHaven pending transactions
                    $pendingTxn->update([
                        'bill_reference' => $safeHavenData['data']['reference'] ?? 'SH-PENDING-' . time(),
                        'vtp_status' => VTPEnums::PENDING,
                        'provider' => 'safehaven',
                        'notes' => 'SafeHaven transaction pending. Reference: ' . ($safeHavenData['data']['reference'] ?? 'N/A'),
                        'vtp_request_id' => $safeHavenData['data']['id'] ?? null
                    ]);
                    
                    DiscordBots::dispatch([
                        'message' => '⏳ **Transaction pending** - SafeHaven processing',
                        'details' => [
                            'user_id' => Auth::id(),
                            'email' => Auth::user()->email,
                            'payment_ref' => $request->payment_reference,
                            'service' => $request->serviceID,
                            'amount' => '₦' . number_format($request->amount),
                            'meter' => $request->billersCode,
                            'safehaven_ref' => $safeHavenData['data']['reference'] ?? 'N/A',
                            'timestamp' => now()->toDateTimeString()
                        ]
                    ]);
                    
                    return response()->json([
                        'message' => 'Transaction is processing via SafeHaven. Please wait a moment.',
                        'pending' => true,
                        'provider' => 'safehaven'
                    ], 200);
                } else {
                    // Log SafeHaven transaction failure (successful HTTP but failed transaction)
                    DiscordBots::dispatch([
                        'message' => '❌ **SafeHaven transaction failed** - Service error',
                        'details' => [
                            'user_id' => Auth::id(),
                            'email' => Auth::user()->email,
                            'payment_ref' => $request->payment_reference,
                            'transaction_status' => $safeHavenData['data']['status'] ?? 'N/A',
                            'error_message' => $safeHavenData['message'] ?? 'Unknown error',
                            'timestamp' => now()->toDateTimeString()
                        ]
                    ]);
                    
                    throw new \Exception('SafeHaven transaction failed: ' . ($safeHavenData['message'] ?? 'Unknown error'));
                }
            } else {
                // Log detailed SafeHaven failure
                DiscordBots::dispatch([
                    'message' => '❌ **SafeHaven API failed** - Network error',
                    'details' => [
                        'user_id' => Auth::id(),
                        'email' => Auth::user()->email,
                        'payment_ref' => $request->payment_reference,
                        'http_status' => $safeHavenResponse->status(),
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);
                
                throw new \Exception('SafeHaven API request failed. HTTP Status: ' . $safeHavenResponse->status() . '. Response: ' . $safeHavenResponse->body());
            }
            
        } catch (\Exception $e) {
            // Both VTPass and SafeHaven failed - NOW refund user (this is the second failure)
            if ($request->fromWallet === true || $request->fromWallet === 'true') {
                MullaUserWallets::where('user_id', Auth::id())->increment('balance', $request->amount);
            }
            
            $pendingTxn->update([
                'vtp_status' => VTPEnums::FAILED,
                'status' => false,
                'provider' => 'both_failed',
                'notes' => 'VTPass failed on first attempt, SafeHaven failed on retry. User refunded. Error: ' . $e->getMessage()
            ]);

            DiscordBots::dispatch([
                'message' => '❌ **Both services failed** - User refunded',
                'details' => [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'payment_ref' => $request->payment_reference,
                    'service' => $request->serviceID,
                    'amount' => '₦' . number_format($request->amount),
                    'meter' => $request->billersCode,
                    'safehaven_error' => $e->getMessage(),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
            
            return response()->json([
                'message' => 'Payment failed on both services. Your wallet has been refunded.',
                'refunded' => true,
            ], 400);
        }
    }

    private function generateRequestId(): string
    {
        $now = now('Africa/Lagos');
        $baseId = $now->format('YmdHi');
        if (strlen($baseId) < 12) {
            $baseId .= str_pad('', 12 - strlen($baseId), '0', STR_PAD_LEFT);
        }
        $randomString = substr(str_shuffle(str_repeat(strtoupper(implode('', range('a', 'z')) . implode('', range(0, 9))), 16)), 0, strlen($baseId) - 12);
        return $baseId . $randomString . uniqid();
    }

    private function isAirtime($value)
    {
        if ($value === 'glo' || $value === 'mtn' || $value === 'airtel' || $value === 'foreign-airtime' || $value === 'etisalat') {
            return true;
        }
        return false;
    }

    private function isElectricity($value)
    {
        if ($value === 'abuja-electric' || $value === 'eko-electric' || $value === 'ibadan-electric' || $value === 'ikeja-electric' || $value === 'jos-electric' || $value === 'kaduna-electric' || $value === 'kano-electric' || $value === 'portharcourt-electric' || $value === 'enugu-electric' || $value === 'benin-electric' || $value === 'aba-electric' || $value === 'yola-electric') {
            return true;
        }
        return false;
    }

    public function isData($value)
    {
        if ($value === 'airtel-data' || $value === 'glo-data' || $value === 'mtn-data' || $value === 'etisalat-data' || $value === 'smile-direct' || $value === 'spectranet' || $value === 'glo-sme-data' || $value === '9mobile-sme-data') {
            return true;
        }
        return false;
    }

    public function cashBack($type)
    {
        if ($type === 'ikeja-electric' || $type === 'abuja-electric' || $type === 'eko-electric' || $type === 'kano-electric' || $type === 'portharcourt-electric' || $type === 'jos-electric' || $type === 'kaduna-electric' || $type === 'enugu-electric' || $type === 'ibadan-electric' || $type === 'benin-electric' || $type === 'aba-electric' || $type === 'yola-electric') {
            return 0.5 / Cashbacks::DIVISOR;
        }

        if ($type === 'airtel-data' || $type === 'glo-data' || $type === 'mtn-data' || $type === 'etisalat-data' || $type === 'smile-direct' || $type === 'spectranet' || $type === 'glo-sme-data' || $type === '9mobile-sme-data') {
            return 1.5 / Cashbacks::DIVISOR;
        }

        if ($this->isAirtime($type)) {
            return 1.5 / Cashbacks::DIVISOR;
        }

        if ($type === 'showmax' || $type === 'dstv' || $type === 'gotv' || $type === 'startimes') {
            return 1.5 / Cashbacks::DIVISOR;
        }

        return 0.5 / Cashbacks::DIVISOR;
    }

    public function payVTPassBillMobile() {}

    public function requeryVTPassBill($id)
    {
        if (!$txn = MullaUserTransactions::find($id)) {
            return response()->json(['message' => 'Transaction not found'], 400);
        }

        if ($txn->vtp_status === VTPEnums::SUCCESS) {
            return response()->json(['message' => 'Transaction already processed succefully.'], 400);
        }

        if ($txn->vtp_status === VTPEnums::REVERSED) {
            return response()->json(['message' => 'Transaction has been reversed.'], 400);
        }

        // Log requery attempt
        DiscordBots::dispatch([
            'message' => '🔍 **Requery attempt** - Checking transaction status',
            'details' => [
                'user_id' => Auth::id(),
                'email' => Auth::user()->email,
                'transaction_id' => $txn->id,
                'payment_ref' => $txn->payment_reference,
                'current_status' => $txn->vtp_status,
                'provider' => $txn->provider ?? 'vtpass',
                'timestamp' => now()->toDateTimeString()
            ]
        ]);

        $pay = Http::withHeaders([
            'api-key' => env('VTPASS_API_KEY'),
            'secret-key' => env('VTPASS_SEC_KEY')
        ])->withOptions([
            'timeout' => 120,
        ])->post($this->vtp_endpoint() . 'requery', [
            'request_id' => $txn->vtp_request_id,
        ]);

        if (!$pay->successful()) {
            return response()->json(['message' => 'An error occured, please try again.'], 400);
        }

        $res = $pay->object();

        if (
            $res->code === '000' &&
            $res->response_description === 'TRANSACTION SUCCESSFUL' &&
            (!empty($res->Token) || !empty($res->token))
        ) {
            $txn = MullaUserTransactions::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $txn->payment_reference
                ],
                [
                    'bill_reference' => $res->content->transactions->transactionId,

                    'unique_element' => $res->content->transactions->unique_element ?? '',
                    'product_name' => $res->content->transactions->product_name ?? '',

                    'cashback' => $txn->cashback,
                    'amount' => $txn->amount,
                    'vat' => $res->Tax ?? $res->mainTokenTax ?? 0,
                    'bill_token' => $res->Reference ?? $res->token ?? $res->Token ?? $res->mainToken ?? '',
                    'bill_units' => $res->Units ?? $res->units ?? $res->mainTokenUnits ?? '',
                    'bill_device_id' => $res->content->transactions->unique_element ?? '',
                    'type' => $res->content->transactions->type ?? '',
                    'voucher_code' => $res->cards[0]->pin ?? '',
                    'voucher_serial' => $res->cards[0]->serialNumber ?? '',
                    'vtp_request_id' => $res->requestId,
                    'vtp_status' => VTPEnums::SUCCESS,
                    'status' => true,
                ]
            );

            Jobs::dispatch([
                'type' => 'transaction_successful',
                'email' => Auth::user()->email,
                'firstname' => Auth::user()->firstname,
                'transfer' => '',
                'utility' => $txn->product_name,
                'amount' => $txn->amount,
                'date' => $txn->date ?? now()->format('D dS M \a\t h:i A'),
                'cashback' => $txn->cashback,
                'code' => $txn->voucher_code ?? '',
                'serial' => '',
                'units'  => $txn->bill_units ?? '',
                'device_id' => $txn->bill_device_id ?? '',
                'token' => $txn->bill_token ?? '',
                'txn_type' => $txn->type ?? '',
                'transaction_reference' => $txn->payment_reference,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]);

            return response($txn, 200);
        } else if ($res->code === '040' && $res->content->transactions->status === 'reversed') {
            DiscordBots::dispatch([
                'message' => '🔄 **Transaction reversed** - User refunded',
                'details' => [
                    'user_id' => Auth::id(),
                    'email' => Auth::user()->email,
                    'transaction_id' => $txn->id,
                    'payment_ref' => $txn->payment_reference,
                    'amount' => '₦' . number_format($txn->amount),
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
            MullaUserTransactions::where('id', $txn->id)->update(['vtp_status' => VTPEnums::REVERSED]);
            MullaUserWallets::where('user_id', Auth::id())->increment('balance', $txn->amount);
            return response(['message' => 'Transaction reversed.'], 400);
        } else if ($res->code === '099' && $res->response_description === 'TRANSACTION PROCESSING - PENDING') {
            return response(['message' => 'Transaction still processing. Please wait a few minutes and try again.'], 400);
        } else {
            return response(['message' => 'Transaction failed. Please try again.'], 400);
        }
    }
}
