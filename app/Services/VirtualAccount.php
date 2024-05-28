<?php

namespace App\Services;

use App\Enums\BaseUrls;
use App\Models\CustomerVirtualAccountsModel;
use App\Traits\Reusables;
use Illuminate\Support\Facades\Http;

class VirtualAccount implements IVirtualAccount
{
    use Reusables;

    public function createCustomer(array $data)
    {
        if (is_null($data['email']) || is_null($data['firstname']) || is_null($data['lastname']) || is_null($data['phone']) || is_null($data['user_id'])) {
            return response(['message' => 'An error occured, check the data you entered.'], 400);
        }

        $pt_customer = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'customer', [
            'email' => $data['email'],
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'phone' => $data['phone'],
        ]);

        CustomerVirtualAccountsModel::updateOrCreate([
            'customer_id' => $pt_customer->object()->data->customer_code ?? null,
        ], [
            'user_id' => $data['user_id'],
        ]);

        return $pt_customer->object();
    }

    public function createVirtualAccount(object $pt_customer, array $data)
    {
        $pt_account = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'dedicated_account', [
            'customer' => $pt_customer->data->customer_code,
            'preferred_bank' => BaseUrls::getBank(),
            'first_name' => $data['firstname'],
            'last_name' => $data['lastname'],
            'phone' => $data['phone'],
        ]);

        CustomerVirtualAccountsModel::where('user_id', $data['user_id'])->update([
            'bank_id' => $pt_account->object()->data->bank->id ?? null,
            'bank_name' => $pt_account->object()->data->bank->name ?? null,
            'bank_slug' => $pt_account->object()->data->bank->slug ?? null,
            'account_name' => $pt_account->object()->data->account_name ?? null,
            'account_number' => $pt_account->object()->data->account_number ?? null,
        ]);

        if ($pt_account->successful()) {
            $this->sendToDiscord('DVA created for ' . $data['firstname'] . ' (ID:' . $data['user_id'] . ')');
            return true;
        } else {
            $this->sendToDiscord('An error occured while creating DVA for ' . $data['firstname'] . ' (ID:' . $data['user_id'] . ')');
            return false;
        }
    }
}
