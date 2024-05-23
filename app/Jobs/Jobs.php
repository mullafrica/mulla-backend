<?php

namespace App\Jobs;

use App\Enums\BaseUrls;
use App\Mail\MullaPasswordResetEmail;
use App\Mail\MullaResetTokenEmail;
use App\Mail\MullaUserFundWalletEmail;
use App\Mail\MullaUserLoginEmail;
use App\Mail\MullaWelcomeEmail;
use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserCashbackWallets;
use App\Models\MullaUserWallets;
use App\Services\VirtualAccount;
use App\Traits\Reusables;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class Jobs implements ShouldQueue
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
        /**
         * 
         * 
         * 1 -> Add to Brevo
         * 2 -> Create Wallet
         * 3 -> Create Cashback Wallet
         * 4 -> Create Paystack Customer
         * 5 -> Create DVA
         * 7 -> Send Email
         * 
         */
        if ($this->data['type'] == 1) {
            // 1 -> Add to Brevo
            Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => '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',
                'content-type' => 'application/json'
            ])->post('https://api.brevo.com/v3/contacts', [
                "attributes" => [
                    "firstname" =>  $this->data['firstname'],
                    "lastname" => $this->data['lastname'],
                ],
                "email" => $this->data['email'],
                "updateEnabled" => false
            ]);

            // 2 -> Create Wallet
            MullaUserWallets::create([
                'user_id' => $this->data['user_id'],
            ]);

            // 3 -> Create Cashback Wallet
            MullaUserCashbackWallets::create([
                'user_id' => $this->data['user_id'],
            ]);

            // 4 -> Create Paystack Customer
            $pt = $va->createCustomer([
                'user_id' => $this->data['user_id'],
                'email' => $this->data['email'],
                'firstname' => $this->data['firstname'],
                'lastname' => $this->data['lastname'],
                'phone' => $this->data['phone'],
            ]);

            // 5 -> Create DVA
            $va->createVirtualAccount($pt, [
                'user_id' => $this->data['user_id'],
                'firstname' => $this->data['firstname'],
                'lastname' => $this->data['lastname'],
                'phone' => $this->data['phone'],
            ]);

            // 7 -> Send Email
            $email = new MullaWelcomeEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] == 2) {
            $email = new MullaResetTokenEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] == 3) {
            $email = new MullaPasswordResetEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] === 'login') {
            $email = new MullaUserLoginEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] === 'fund_wallet') {
            $email = new MullaUserFundWalletEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }
    }
}
