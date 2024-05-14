<?php

namespace App\Http\Controllers;

use App\Enums\BaseUrls;
use App\Enums\Cashbacks;
use App\Jobs\DiscordBots;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserMeterNumbers;
use App\Models\MullaUserTransactions;
use App\Services\WalletService;
use App\Traits\Reusables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MullaBillController extends Controller
{
    use Reusables;

    public $vtp_endpoint = "https://api-service.vtpass.com/api/";

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
        $meters = MullaUserMeterNumbers::where('user_id', Auth::id())->get();
        return response()->json($meters, 200);
    }

    public function payABill(Request $request)
    {
        $request->validate([
            'bill' => 'required',
            'email' => 'required',
            'operator_id' => 'required',
            'amount' => 'required'
        ]);

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
            ]
        );

        return response()->json(['message' => 'Transaction stored successfully'], 200);
    }

    public function getUserTxns()
    {
        return response()->json(MullaUserTransactions::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get(), 200);
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
            $ops = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'public-key' => env('VTPASS_PUB_KEY')
            ])->get($this->vtp_endpoint . 'services?identifier=' . $identifier);

            $data = $ops->json();

            $mappedContent = array_map(function ($service) {
                $service['id'] = $service['serviceID']; // Create a new 'id' property
                unset($service['serviceID']); // Remove the original 'serviceID' property
                return $service;
            }, $data['content']);

            $response['data'] = $mappedContent;

            return $response;

            return [

                'data' => $data['data']
            ];
        } else {
            return response()->json(['error' => 'Bill identifier not provided'], 400);
        }
    }

    public function getVTPassOperatorProductVariation(Request $request)
    {
        if ($request->id) {
            $ops = Http::withHeaders([
                'api-key' => env('VTPASS_API_KEY'),
                'public-key' => env('VTPASS_PUB_KEY')
            ])->get($this->vtp_endpoint . 'service-variations?serviceID=' . $request->id);

            return $ops->json();
            // return $ops->object()->content->variations;
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
            ])->post($this->vtp_endpoint . 'merchant-verify?billersCode=' . $request->device_number . '&serviceID=' . $op_id . '&type=' . $request->meter_type);

            $data = $validate->object();

            if (!isset($data->content->error)) {
                // Fetch and store data
                $device = $validate->object();

                // Store meter for user
                MullaUserMeterNumbers::updateOrCreate([
                    'meter_number' => $request->device_number,
                ], [
                    'user_id' => Auth::id(),
                    'name' => $device->content->Customer_Name,
                    'meter_type' => $device->content->Meter_Type,
                    'address' => $device->content->Address
                ]);

                return response()->json([
                    'data' => [
                        "address" => $device->content->Address,
                        "name" => $device->content->Customer_Name
                    ]
                ], 200);
            } else {
                return response()->json($validate->json(), 400);
            }
        } else {
            return response()->json(['error' => 'Meter or a required parameter not provided'], 400);
        }
    }

    public function payVTPassBill(Request $request, WalletService $ws)
    {
        $request->validate([
            'bill' => 'required',
            'operator_id' => 'required',
            'amount' => 'required',
            'fromWallet' => 'required'
        ]);

        if ($request->amount < 500) {
            return response(['message' => 'Minimum amount is 500'], 400);
        }

        if ($request->fromWallet == 'true') {
            if (!$ws->checkBalance($request->amount * BaseUrls::MULTIPLIER)) {
                return response(['message' => 'Low wallet balance'], 200);
            } else {
                $ws->decrementBalance($request->amount);
            }
        }

        if ($request->bill == 'electricity') {
            $request->validate([
                'meter_type' => 'required',
                'device_number' => 'required',
            ]);
        }

        $user =  Auth::user();
        // Pass the bill to this endpoint and return the response
        $pay = Http::withHeaders([
            'api-key' => env('VTPASS_API_KEY'),
            'secret-key' => env('VTPASS_SEC_KEY')
        ])->post($this->vtp_endpoint . 'pay?request_id=' . $this->generateRequestId() . '&serviceID=' . $request->operator_id . '&billersCode=' . $request->device_number . '&variation_code=' . $request->meter_type . '&amount=' . $request->amount . '&phone=' . $user->phone);

        $res = $pay->object();

        DiscordBots::dispatch(['message' => json_encode($res)]);

        if (isset($res->Token) && isset($res->Reference)) {
            // Credit cashback wallet with 1.5% cashback, create a cashback enum
            MullaUserCashbackWallets::updateOrCreate(['user_id' => Auth::id()])
                ->increment('balance', $request->amount * Cashbacks::ELECTRICITY_AEDC);

            MullaUserTransactions::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $request->reference
                ],
                [
                    'bill_reference' => $res->content->transactions->transactionId,
                    'cashback' => $request->amount * Cashbacks::ELECTRICITY_AEDC,
                    'amount' => $request->amount,
                    'vat' => $res->Tax ?? 0,
                    'bill_token' => $res->Reference ?? $res->Token,
                    'bill_units' => $res->Units ?? '',
                    'bill_device_id' => $res->content->transactions->unique_element ?? '',
                    'type' => $res->content->transactions->type ?? '',
                ]
            );

            return response()->json($pay->json(), 200);
        }

        if (isset($res->code) && $res->code === "000" && $res->content->transactions->status === "pending") {
            MullaUserTransactions::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $request->reference
                ],
                [
                    'bill_reference' => $res->content->transactions->transactionId,
                    'amount' => $request->amount,
                    'bill_device_id' => $res->content->transactions->unique_element,
                    'type' => $res->content->transactions->type,
                ]
            );

            return response()->json(['message' => 'Disco temporarily down.'], 400);
        }

        return response()->json(['message' => 'Something went wrong, try again later.'], 400);
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
}
