<?php

namespace App\Jobs;

use App\Models\Business\MullaBusinessBulkTransferListItemsModel;
use App\Models\Business\MullaBusinessBulkTransferTransactionsAlpha;
use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Traits\Reusables;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WebhookJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Reusables;

    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add transfer to customer wallet
        if ($this->data['event'] === 'charge.success' && $this->data['data']['channel'] === 'dedicated_nuban') {
            // Add transfer to customer wallet
            if ($cvam = CustomerVirtualAccountsModel::where('customer_id', $this->data['data']['customer']['customer_code'])->first()) {
                $amount = $this->data['data']['amount'] - $this->data['data']['fees'];
                MullaUserWallets::where('user_id', $cvam->user_id)->increment('balance', $amount / 100);

                MullaUserTransactions::create([
                    'type' => 'Deposit',
                    'amount' => $amount / 100,
                    'payment_reference' => $this->data['data']['reference'],
                    'user_id' => $cvam->user_id,
                    'fees' => $this->data['data']['fees'] / 100,
                ]);

                Jobs::dispatch([
                    'type' => 'fund_wallet',
                    'email' => User::find($cvam->user_id)->email,
                    'firstname' => User::find($cvam->user_id)->firstname,
                    'amount' => $amount / 100,
                    'fee' => $this->data['data']['fees'] / 100,
                    'sender' => $this->data['data']['authorization']['sender_name'],
                    'bank' => $this->data['data']['authorization']['sender_bank'],
                    'transaction_reference' => $this->data['data']['reference'],
                    'description' => $this->data['data']['authorization']['narration'],
                    'date' => Carbon::parse($this->data['data']['paid_at'])->isoFormat('lll'),
                    'status' => $this->data['data']['status']
                ]);

                $this->sendToDiscord('New transfer to customer wallet has been made. (ID:' . $cvam->user_id . ') ' . 'Amount: ' . $amount / 100 . ' NGN,' . ' ' . User::find($cvam->user_id)->email);
            }
        }

        /**
         * 
         * Handle Bulk Transfer Data
         * 
         */
        if ($this->data['event'] === 'transfer.success' && $this->data['data']['status'] === 'success') {
            sleep(2);
            
            $this->sendToDiscord('transfer.success');

            MullaBusinessBulkTransferTransactionsAlpha::where('reference', $this->data['data']['reference'])->update([
                'transfer_code' => $this->data['data']['transfer_code'],
                'status' => $this->data['data']['status'] === 'success' ? true : false,
                'currency' => $this->data['data']['currency'],
            ]);

            $item = MullaBusinessBulkTransferListItemsModel::where('recipient_code', $this->data['data']['recipient']['recipient_code'])->first();

            if ($item) {
                $item->update([
                    'account_name' => $this->data['data']['details']['account_name'] ?? $item->account_name,
                    'bank_code' => $this->data['data']['details']['bank_code'] ?? $item->bank_code,
                    'bank_name' => $this->data['data']['details']['bank_name'] ?? $item->bank_name
                ]);
            }
        }
    }
}
