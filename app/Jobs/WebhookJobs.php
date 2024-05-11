<?php

namespace App\Jobs;

use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Traits\Reusables;
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

                // Create transaction
                MullaUserTransactions::create([
                    'type' => 'Deposit',
                    'amount' => $amount / 100,
                    'payment_reference' => $this->data['data']['reference'],
                    'user_id' => $cvam->user_id,
                    'fees' => $this->data['data']['fees'] / 100,
                ]);

                $this->sendToDiscord('New transfer to customer wallet has been made. (ID:' . $cvam->user_id . ') ' . 'Amount: ' . $amount / 100 . ' NGN,' . ' ' . User::find($cvam->user_id)->email);
            }
        }
    }
}
