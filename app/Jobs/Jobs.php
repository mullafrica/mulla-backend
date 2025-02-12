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
use App\Models\MullaUserIPDetailsModel;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Services\CustomerIoService;
use App\Services\PushNotification;
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
        if ($this->data['type'] === 'validate_bvn') {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('MULLA_PAYSTACK_LIVE'),
            ])
                ->post('https://api.paystack.co/customer/' . $this->data['pt'] . '/identification', [
                    'first_name' => $this->data['firstname'],
                    'last_name' => $this->data['lastname'],
                    'country' => 'NG',
                    'type' => 'bank_account',
                    'account_number' => $this->data['nuban'],
                    'bvn' => $this->data['bvn'],
                    'bank_code' => $this->data['bank_code'],
                ]);

            $this->sendToDiscord('BVN validation in progress.' . ' (ID:' . $this->data['firstname'] . ' ' . $this->data['lastname'] . ' (pt->' . json_encode($response->json()) . ')');
        }

        if ($this->data['type'] === 'create_account') {
            // 7 -> Send Email
            $email = new MullaWelcomeEmail($this->data);
            Mail::to($this->data['email'])->send($email);

            // 8 -> Add user to convertkit
            if (env('APP_ENV') === 'production') {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'charset' => 'utf-8'
                ])
                    ->post('https://api.convertkit.com/v3/forms/6862352/subscribe', [
                        'api_key' => env('CONVERTKIT_KEY'),
                        'email' => $this->data['email'],
                        'first_name' => $this->data['firstname'],
                        'phone' => $this->data['phone']
                    ]);
            }

            // 1 -> Add to CustomerIO
            $customerIO = new CustomerIoService();
            $customerIO->identifyUser($this->data);

            $info = $this->getUserDetails($this->data['ip']);

            MullaUserIPDetailsModel::create([
                'user_id' => User::where('email', $this->data['email'])->value('id'),
                'ip' => $this->data['ip'],
                'browser' => $this->data['browser'],
                'platform' => $this->data['platform'],
                'location' =>  $info['location']['city'] . ', ' . $info['location']['country'] . ', ' . $info['location']['latitude'] . ', ' . $info['location']['longitude'],
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

            $info = $this->getUserDetails($this->data['ip']);

            MullaUserIPDetailsModel::updateOrCreate([
                'user_id' => User::where('email', $this->data['email'])->value('id'),
                'ip' => $this->data['ip'],
            ], [
                'browser' => $this->data['browser'],
                'platform' => $this->data['platform'],
                'location' =>  $info['location']['city'] . ', ' . $info['location']['country'] . ', ' . $info['location']['latitude'] . ', ' . $info['location']['longitude'],
            ]);
        }

        if ($this->data['type'] === 'fund_wallet') {
            $pt = new PushNotification();

            if ($fcmToken = User::where('email', $this->data['email'])->value('fcm_token')) {
                $pt->send([
                    'to' => $fcmToken,
                    'title' => 'Mulla',
                    'body' => 'Your account just got funded with ' . number_format($this->data['amount'], 2) . ' NGN.'
                ]);
            }

            $customerIO = new CustomerIoService();
            $customerIO->trackEvent($this->data, 'fund_wallet');

            $email = new MullaUserFundWalletEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] === 'verify_email') {
            $email = new MullaVerifyUserEmail($this->data);
            Mail::to($this->data['email'])->send($email);
        }

        if ($this->data['type'] === 'transaction_successful') {
            $pt = new PushNotification();

            if ($fcmToken = User::where('email', $this->data['email'])->value('fcm_token')) {
                $pt->send([
                    'to' => $fcmToken,
                    'title' => 'Mulla',
                    'body' => 'Your ' . $this->data['utility'] . 'Purchase was successful.'
                ]);
            }

            $customerIO = new CustomerIoService();

            // Track event in CustomerIO
            if ($this->data['txn_type'] === 'Electricity Bill') {
                $customerIO->trackEvent($this->data, 'electricity_successful');
            }

            if ($this->data['txn_type'] === 'Airtime Recharge') {
                $customerIO->trackEvent($this->data, 'airtime_successful');
            }

            $email = new MullaUserTransactionEmail($this->data);
            Mail::to($this->data['email'])->send($email);

            // if ($this->data['type'] === 'Data Purchase') {
            //     $customerIO->trackEvent($this->data, 'data_successful');
            // }

            // if ($this->data['type'] === 'Cable Subscription') {
            //     $customerIO->trackEvent($this->data, 'cable_successful');
            // }
        }
    }
}
