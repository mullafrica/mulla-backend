<?php

namespace App\Services;

use App\Enums\BaseUrls;
use App\Jobs\MullaBusinessJobs;
use App\Models\Business\MullaBusinessBulkTransferAlpha;
use App\Models\Business\MullaBusinessBulkTransferListItemsModel;
use App\Models\Business\MullaBusinessBulkTransferListModel;
use App\Models\Business\MullaBusinessBulkTransferTransactionsAlpha;
use App\Services\Interfaces\IBulkTransferService;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class BulkTransferService implements IBulkTransferService
{
    use UniqueId, Reusables;

    public function createList(array $data, string $name)
    {        
        // Convert keys to slugs
        foreach ($data as &$item) {
            // Rename "Account Name" to "name"
            if (isset($item['Account Name'])) {
                $item['name'] = $item['Account Name'];
                unset($item['Account Name']);
            }

            // Add 'type' => 'nuban'
            $item['type'] = 'nuban';

            $item['metadata'] = [
                'business_id' => Auth::user()->id,
                'email' => $item['Email Address'] ?? '',
                'amount' => $item['Amount'] ?? 0,
            ];

            // Slug every property name
            foreach ($item as $key => $value) {
                $sluggedKey = Str::slug($key, '_');
                $item[$sluggedKey] = $value;
                if ($sluggedKey !== $key) {
                    unset($item[$key]);
                }
            }
        }

        $pt_customer = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'transferrecipient/bulk', [
            'batch' => $data,
        ]);

        $response = $pt_customer->json();

        if (isset($response['data']['errors']) && !empty($response['data']['errors'])) {
            // Return early if there are errors
            return response(['status' => false, 'message' => 'Errors found in response', 'errors' => $response['data']['errors']], 400);
        }

        // Create list for business - business_id, amount, bank_code, bank_name, account_number, account_name, email, recp_code
        $btl = MullaBusinessBulkTransferListModel::create([
            'business_id' => Auth::user()->id,
            'title' => $name,
        ]);

        // Loop through the success array and create items
        foreach ($response['data']['success'] as $item) {
            MullaBusinessBulkTransferListItemsModel::updateOrCreate([
                'list_id' => $btl->id,
                'account_number' => $item['details']['account_number'] ?? 'n/a',
                'email' => $item['metadata']['email'] ?? 'n/a',
            ], [
                'amount' => $item['metadata']['amount'] ?? 0, // Ensure you have the correct field or assign a default value
                'account_name' => $item['name'] ?? 'n/a',
                'bank_name' => $item['details']['bank_name'] ?? 'n/a',
                'recipient_code' => $item['recipient_code'],
                'bank_code' => $item['details']['bank_code'] ?? 'n/a',
            ]);
        }

        return response($response, 200);
    }

    public function getLists()
    {
        return MullaBusinessBulkTransferListModel::where('business_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
    }

    public function getListItems(string $id)
    {
        return MullaBusinessBulkTransferListItemsModel::where('list_id', $id)->orderBy('created_at', 'desc')->get();
    }

    public function deleteList(string $id)
    {
        $list = MullaBusinessBulkTransferListModel::where('id', $id)->with('items')->first();

        if (!$list) {
            return response(['status' => false, 'message' => 'List not found'], 400);
        }

        MullaBusinessJobs::dispatch([
            'type' => 'delete_trf_recipients',
            'list' => $list->items ?? []
        ]);        

        $list->items()->delete();

        $list->delete();

        return response(['status' => true, 'message' => 'List deleted successfully'], 200);
    }

    /**
     * 
     * Initiate transfer to recipient
     * 
     */
    public function initiateTransfer(string $id) {
        $list = MullaBusinessBulkTransferListModel::find($id);

        $trf = MullaBusinessBulkTransferAlpha::create([
            'business_id' => Auth::user()->id,
            'list_id' => $id,
        ]);

        foreach ($list->items as $item) {
            MullaBusinessBulkTransferTransactionsAlpha::create([
                'transfer_id' => $trf->id,
                'recipient_code' => $item->recipient_code,
                'amount' => $item->amount * 100,
                'reference' => $this->uuid16()
            ]);
        }

        $transfers = $trf->transactions->map(function ($item) use ($list) {
            return [
                "amount" => $item->amount,
                "reference" => $item->reference,
                "reason" => $list->title,
                "recipient" => $item->recipient_code,
            ];
        })->toArray();

        $data = [
            "currency" => "NGN",
            "source" => "balance",
            "transfers" => $transfers,
        ];

        $pt_customer = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->post(BaseUrls::PAYSTACK . 'transfer/bulk', $data);

        $response = $pt_customer->json();

        if ($response['status'] === false) {
            $this->sendToDiscord('Business with (ID- ' . Auth::user()->id . ') initiated transfer failed: ' . $response['message']);
            return false;
        }

        $this->sendToDiscord('Business with (ID- ' . Auth::user()->id . ') has initiated a transfer: ' . $response['message']);
        
        return true;
    }
}
