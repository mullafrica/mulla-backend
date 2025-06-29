<?php

namespace App\Jobs;

use App\Models\Business\MullaBusinessBulkTransferListItemsModel;
use App\Models\Business\MullaBusinessBulkTransferTransactionsAlpha;
use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Services\VirtualAccount;
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
    public function handle(VirtualAccount $va): void
    {
        // Add transfer to customer wallet
        if ($this->data['event'] === 'charge.success' && $this->data['data']['channel'] === 'dedicated_nuban') {
            // Add transfer to customer wallet
            if ($cvam = CustomerVirtualAccountsModel::where('customer_id', $this->data['data']['customer']['customer_code'])->first()) {
                $amount = $this->data['data']['amount'] - $this->data['data']['fees'];
                $user = User::find($cvam->user_id);
                $oldBalance = MullaUserWallets::where('user_id', $cvam->user_id)->value('balance') ?? 0;
                
                MullaUserWallets::where('user_id', $cvam->user_id)->increment('balance', $amount / 100);
                
                // Enhanced Discord logging for wallet funding
                DiscordBots::dispatch([
                    'message' => '💳 **Wallet funded** - Bank transfer received',
                    'details' => [
                        'user_id' => $cvam->user_id,
                        'email' => $user->email,
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'amount' => '₦' . number_format($amount / 100),
                        'sender_name' => $this->data['data']['authorization']['sender_name'] ?? 'Unknown',
                        'sender_bank' => $this->data['data']['authorization']['sender_bank'] ?? 'Unknown',
                        'reference' => $this->data['data']['reference'],
                        'timestamp' => now()->toDateTimeString()
                    ]
                ]);

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

            }
        }

        /**
         * 
         * Handle Bulk Transfer Data for Mulla Business
         * 
         */
        if ($this->data['event'] === 'transfer.success' && $this->data['data']['status'] === 'success') {
            // Mulla Business
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

            // Mulla User (What to do here?)

            DiscordBots::dispatch([
                'message' => '💸 **Transfer successful** - Business bulk transfer',
                'details' => [
                    'reference' => $this->data['data']['reference'],
                    'amount' => '₦' . number_format($this->data['data']['amount'] / 100),
                    'recipient' => $this->data['data']['recipient']['name'] ?? 'N/A',
                    'bank' => $this->data['data']['recipient']['details']['bank_name'] ?? 'N/A',
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        }

        /**
         * 
         * BVN Customer Verification & DVA Creation
         * 
         */
        if ($this->data['event'] === 'customeridentification.success') {


            $cvam = CustomerVirtualAccountsModel::where('customer_id', $this->data['data']['customer_code'])->first();

            if (!$cvam) {
                return;
            }

            $user = User::find($cvam->user_id);

            $va->createVirtualAccount((object)[
                'data' => (object)[
                    'customer_code' => $this->data['data']['customer_code'],
                ],
            ], [
                'user_id' => $cvam->user_id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
            ]);
        }

        if ($this->data['event'] === 'customeridentification.failed') {
            DiscordBots::dispatch([
                'message' => '❌ **Customer verification failed** - BVN validation error',
                'details' => [
                    'customer_code' => $this->data['data']['customer_code'] ?? 'N/A',
                    'error' => $this->data['data']['reason'] ?? 'Unknown error',
                    'timestamp' => now()->toDateTimeString()
                ]
            ]);
        }
    }
}
