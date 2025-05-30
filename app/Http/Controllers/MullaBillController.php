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
use App\Traits\Reusables;
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
            // Pass the meter to this endpoint and return the response
            $validate = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'secret-key' => env('VTPASS_SEC_KEY')
            ])->withOptions([
                'timeout' => 120,
            ])->post($this->vtp_endpoint() . 'merchant-verify?billersCode=' . $request->device_number . '&serviceID=' . $op_id . '&type=' . $request->meter_type);

            $data = $validate->object();

            if (!isset($data->content->error) && $validate->status() === 200) {
                // Fetch and store data
                $device = $validate->object();

                // Check if the meter number has two duplicates and delete only the first one
                if (MullaUserMeterNumbers::where('meter_number', $request->device_number)->count() > 1) {
                    MullaUserMeterNumbers::where('meter_number', $request->device_number)->first()->delete();
                }

                // Store meter for user
                MullaUserMeterNumbers::updateOrCreate([
                    'meter_number' => $request->device_number,
                    'user_id' => Auth::id(),
                ], [
                    'address' => $device->content->Address ?? '',
                    'name' => $device->content->Customer_Name ?? '',
                    'meter_type' => $device->content->Meter_Type ?? $request->meter_type,
                    'disco' => $op_id ?? ''
                ]);

                return response()->json([
                    'data' => [
                        "address" => $device->content->Address ?? '',
                        "name" => $device->content->Customer_Name ?? ''
                    ]
                ], 200);
            } else {
                return response()->json($validate->json(), 400);
            }
        } else {
            return response()->json(['error' => 'Meter or a required parameter not provided'], 400);
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

        $request->validate([
            'payment_reference' => 'required',
            'serviceID' => 'required',
            'billersCode' => 'required',
            'amount' => 'required|numeric|min:0',
            'fromWallet' => 'required'
        ]);

        $amount = $request->amount;

        /** Check if amount is numeric and greater than 0 (whitelist) */
        if (!is_numeric($amount) || $amount <= 0) {
            return response()->json(['message' => 'Invalid amount provided. Please provide a valid amount.'], 400);
        }

        /** Check if amount is greater than 500 for electricity */
        if ($this->isElectricity($request->serviceID) && $amount < 1000) {
            return response()->json(['message' => 'Minimum amount for electricity is 1000.'], 400);
        }

        /** Unique payment reference for each transaction */
        if (!MullaUserTransactions::where('payment_reference', $request->payment_reference)->where('status', false)->exists()) {
            return response(['message' => 'Payment ref error.'], 400);
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

        /** Check wallet if true */
        if ($request->fromWallet == 'true') {
            if (!$ws->checkBalance($amount * BaseUrls::MULTIPLIER)) {
                return response(['message' => 'Low wallet balance.'], 200);
            } else {
                $ws->decrementBalance($amount);
            }
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
            // TODO: turn on refund user when we fix the issue with validating input
            // MullaUserWallets::where('user_id', Auth::id())->increment('balance', $amount);
            DiscordBots::dispatch(['message' => 'An error occured, check and refund user with (ID:' . Auth::id() . ') - ' . $request->serviceID . ' ' . json_encode($res)]);
            return response(['message' => 'An error occured, please contact support.'], 400);
        }

        /** Log the response to discord */
        DiscordBots::dispatch(['message' => json_encode($res)]);

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

            MullaUserTransactions::updateOrCreate(
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

            return response()->json(['message' => 'We are processing your transaction, please wait a moment.', 'pending' => true], 200);
        } else {
            /**
             * We are assuming the transaction failed here, nothing
             * should be getting stored.
             */
            return response()->json(['message' => 'Service not available at the moment, please try again later.'], 400);
        }

        return response()->json(['message' => 'An error occured, please contact support.'], 400);
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
            DiscordBots::dispatch(['message' => 'Transaction - ' . $res->content->transactions->type . ' - has been reversed, user has been refunded (ID:' . Auth::id() . ')']);
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
