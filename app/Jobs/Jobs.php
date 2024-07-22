<?php

namespace App\Jobs;

use App\Enums\BaseUrls;
use App\Mail\MullaPasswordResetEmail;
use App\Mail\MullaResetTokenEmail;
use App\Mail\MullaUserFundWalletEmail;
use App\Mail\MullaUserLoginEmail;
use App\Mail\MullaUserTransactionEmail;
use App\Mail\MullaVerifyUserEmail;
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
         * 2 -> Create Wallet [disable]
         * 3 -> Create Cashback Wallet [disable]
         * 4 -> Create Paystack Customer [disable]
         * 5 -> Create DVA [disable]
         * 7 -> Send Email
         * 
         */
        if ($this->data['type'] === 'create_account') {
            // 1 -> Add to Brevo
            
            // if (env('APP_ENV') === 'production') {
            //     Http::withHeaders([
            //         'accept' => 'application/json',
            //         'api-key' => env('BREVO_KEY'),
            //         'content-type' => 'application/json'
            //     ])->post('https://api.brevo.com/v3/contacts', [
            //         "attributes" => [
            //             "firstname" =>  $this->data['firstname'],
            //             "lastname" => $this->data['lastname'],
            //         ],
            //         "email" => $this->data['email'],
            //         "updateEnabled" => false
            //     ]);
            // }

            // 7 -> Send Email
            $email = new MullaWelcomeEmail($this->data);
            Mail::to($this->data['email'])->send($email);

            // -> Add user to convertkit
            Http::withHeaders([
                'Content-Type' => 'application/json',
                'charset' => 'utf-8'
            ])
            ->post('https://api.convertkit.com/v3/forms/6862352/subscribe', [
                'api_key' => 'E1zguteKG8p0k5i-XuI4kg',
                'email' => $this->data['email'],
                'first_name' => $this->data['firstname'],
                'phone' => $this->data['phone']
            ]);
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

        if ($this->data['type'] === 'verify_email') {
            $email = new MullaVerifyUserEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] === 'transaction_successful') {
            $email = new MullaUserTransactionEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }
    }
}
