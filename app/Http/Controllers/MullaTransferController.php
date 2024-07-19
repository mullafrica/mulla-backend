<?php

namespace App\Http\Controllers;

use App\Enums\BaseUrls;
use App\Jobs\DiscordBots;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserTransferBeneficiariesModel;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Jobs\Jobs;

class MullaTransferController extends Controller
{
    public function getBanks()
    {
        return Cache::remember('pt_banks', 60 * 24 * 30, function () {
            $data = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->get(BaseUrls::PAYSTACK . 'bank');
            $banks = $data->object()->data;

            // Extract only the required fields
            $filteredBanks = array_map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'slug' => $bank->slug,
                    'code' => $bank->code
                ];
            }, $banks);

            return $filteredBanks;
        });
    }

    public function getBeneficiaries()
    {
        return MullaUserTransferBeneficiariesModel::where('user_id', Auth::id())->get();
    }

    public function saveBeneficiaries(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'bank' => 'required',
            'number' => 'required'
        ]);

        $b = MullaUserTransferBeneficiariesModel::updateOrCreate([
            'user_id' => Auth::id(),
            'number' => $request->number
        ], [
            'name' => $request->name,
            'bank' => $request->bank
        ]);

        return response()->json(['message' => 'Beneficiary saved successfully', 'data' => $b], 200);
    }

    public function validateAccount(Request $request)
    {
        $request->validate([
            'bank_code' => 'required',
            'account_number' => 'required'
        ]);


        $pt_customer = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'transferrecipient', [
            "type" => "nuban",
            // "name" => "Tolu Robert",
            "account_number" => $request->account_number,
            "bank_code" => $request->bank_code,
            "currency" => "NGN"
        ]);

        if ($pt_customer->object()->status === false) {
            return response([
                // 'message' => $pt_customer->object()->message
                'message' => 'Cannot resolve account, please check the account number and bank.'
            ], 400);
        }

        return response([
            'bank' => $pt_customer->object()->data->details->bank_name,
            'number' => $pt_customer->object()->data->details->account_number,
            'name' => $pt_customer->object()->data->details->account_name,
            'code' => $pt_customer->object()->data->details->bank_code,
            'recipient_code' => $pt_customer->object()->data->recipient_code

        ], 200);
    }

    public function completeTransfer(Request $request, WalletService $ws)
    {
        $request->validate([
            'recipient_code' => 'required',
            'amount' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'bank' => 'required'
        ]);

        if ($ws->checkBalance($request->amount * BaseUrls::MULTIPLIER)) {

            $transfer = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'transfer', [
                "source" => "balance",
                "amount" => $request->amount * 100,
                "recipient" => $request->recipient_code,
                "reason" => 'MULLA/TRF/' . $request->account_name
            ]);

            if ($transfer->object()->status === false) {
                return response([
                    'message' => $transfer->object()->message
                ], 400);
            }

            if ($transfer->object()->status === true && $transfer->object()->data->status === 'pending') {
                // Decrement wallet balance
                $ws->decrementBalance($request->amount);

                $txn = MullaUserTransactions::create(
                    [
                        'user_id' => Auth::id(),
                        'payment_reference' => $transfer->object()->data->reference,
                        'unique_element' => $request->account_number,
                        'type' => "Bank Transfer",
                        'product_name' => $request->account_name . ' / ' . $request->bank . ' (' . $request->account_number . ')',
                        'amount' => $request->amount,
                        'status' => true,
                    ]
                );

                DiscordBots::dispatch(['message' => 'User (' . Auth::user()->email . ') just made a transfer (NGN' . ($request->amount) . ')']);

                // Send Email
                Jobs::dispatch([
                    'type' => 'transaction_successful',
                    'email' => Auth::user()->email,
                    'firstname' => Auth::user()->firstname,
                    'utility' => '',
                    'transfer' => $request->account_name . ' / ' . $request->bank . ' (' . $request->account_number . ')',
                    'amount' => $request->amount,
                    'date' => $txn->date ?? now()->format('D dS M \a\t h:i A'),
                    'cashback' => '',
                    'code' => '',
                    'serial' => '',
                    'units'  => '',
                    'device_id' => '',
                    'token' => '',
                    'transaction_reference' => $transfer->object()->data->reference,
                ]);

                return response(['txn' => $txn, 'message' => 'Transaction successful.'], 200);
            }
        } else {
            return response(['message' => 'Your balance is insufficient, please fund your wallet.', 'status' => false], 400);
        }
    }
}
