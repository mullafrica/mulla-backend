<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserMeterNumbers;
use App\Models\MullaUserAirtimeNumbers;
use App\Enums\VTPEnums;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $transactionCount = 0;

        foreach ($users as $user) {
            // Create 5-15 transactions per user over the last 30 days
            $transactionsToCreate = rand(5, 15);
            
            for ($i = 0; $i < $transactionsToCreate; $i++) {
                $transactionCount += $this->createRandomTransaction($user, $i);
            }
        }

        $this->command->info("✅ Created {$transactionCount} user transactions");
    }

    private function createRandomTransaction($user, $index): int
    {
        $transactionTypes = [
            'electricity' => 40, // 40% chance
            'airtime' => 30,     // 30% chance  
            'data' => 20,        // 20% chance
            'tv' => 10,          // 10% chance
        ];

        $type = $this->getWeightedRandomType($transactionTypes);
        
        // Generate transaction date within last 30 days
        $transactionDate = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
        
        switch ($type) {
            case 'electricity':
                return $this->createElectricityTransaction($user, $transactionDate, $index);
            case 'airtime':
                return $this->createAirtimeTransaction($user, $transactionDate, $index);
            case 'data':
                return $this->createDataTransaction($user, $transactionDate, $index);
            case 'tv':
                return $this->createTvTransaction($user, $transactionDate, $index);
            default:
                return 0;
        }
    }

    private function createElectricityTransaction($user, $transactionDate, $index): int
    {
        $meterNumbers = MullaUserMeterNumbers::where('user_id', $user->id)->get();
        if ($meterNumbers->isEmpty()) {
            return 0;
        }

        $meter = $meterNumbers->random();
        $amount = rand(2000, 20000); // ₦2,000 - ₦20,000
        $cashback = $amount * 0.005; // 0.5% cashback for electricity
        
        $status = rand(1, 100) <= 95; // 95% success rate
        $provider = rand(1, 100) <= 85 ? 'vtpass' : 'safehaven'; // 85% vtpass, 15% safehaven

        MullaUserTransactions::create([
            'user_id' => $user->id,
            'payment_reference' => 'ELEC_' . strtoupper(uniqid()),
            'bill_reference' => $status ? 'TXN_' . rand(1000000000, 9999999999) : null,
            'amount' => $amount,
            'cashback' => $status ? $cashback : 0,
            'vat' => $amount * 0.075, // 7.5% VAT
            'type' => 'Electricity Bill',
            'bill_token' => $status ? $this->generateElectricityToken() : null,
            'bill_units' => $status ? number_format(rand(50, 500), 2) . ' kWh' : null,
            'bill_device_id' => $meter->meter_number,
            'unique_element' => $meter->meter_number,
            'product_name' => $this->getElectricityProductName($meter->disco, $provider),
            'status' => $status,
            'vtp_request_id' => 'REQ_' . rand(100000000, 999999999),
            'vtp_status' => $status ? VTPEnums::SUCCESS : VTPEnums::FAILED,
            'provider' => $provider,
            'created_at' => $transactionDate,
            'updated_at' => $transactionDate,
        ]);

        return 1;
    }

    private function createAirtimeTransaction($user, $transactionDate, $index): int
    {
        $airtimeNumbers = MullaUserAirtimeNumbers::where('user_id', $user->id)->get();
        if ($airtimeNumbers->isEmpty()) {
            return 0;
        }

        $airtimeNumber = $airtimeNumbers->random();
        $amount = [100, 200, 500, 1000, 2000, 5000][rand(0, 5)]; // Common airtime amounts
        $cashback = $amount * 0.015; // 1.5% cashback for airtime
        
        $status = rand(1, 100) <= 98; // 98% success rate for airtime

        MullaUserTransactions::create([
            'user_id' => $user->id,
            'payment_reference' => 'AIRTIME_' . strtoupper(uniqid()),
            'bill_reference' => $status ? 'TXN_' . rand(1000000000, 9999999999) : null,
            'amount' => $amount,
            'cashback' => $status ? $cashback : 0,
            'type' => 'Airtime Recharge',
            'unique_element' => $airtimeNumber->phone_number,
            'product_name' => $this->getAirtimeProductName($airtimeNumber->telco),
            'status' => $status,
            'vtp_request_id' => 'REQ_' . rand(100000000, 999999999),
            'vtp_status' => $status ? VTPEnums::SUCCESS : VTPEnums::FAILED,
            'provider' => 'vtpass',
            'created_at' => $transactionDate,
            'updated_at' => $transactionDate,
        ]);

        return 1;
    }

    private function createDataTransaction($user, $transactionDate, $index): int
    {
        $dataNumbers = MullaUserAirtimeNumbers::where('user_id', $user->id)->get();
        if ($dataNumbers->isEmpty()) {
            return 0;
        }

        $dataNumber = $dataNumbers->random();
        $dataPlans = [
            ['amount' => 500, 'size' => '1.5GB'],
            ['amount' => 1000, 'size' => '3GB'],
            ['amount' => 1500, 'size' => '5GB'],
            ['amount' => 2000, 'size' => '8GB'],
            ['amount' => 3000, 'size' => '12GB'],
            ['amount' => 5000, 'size' => '20GB'],
        ];
        
        $plan = $dataPlans[rand(0, count($dataPlans) - 1)];
        $amount = $plan['amount'];
        $cashback = $amount * 0.015; // 1.5% cashback for data
        
        $status = rand(1, 100) <= 96; // 96% success rate for data

        MullaUserTransactions::create([
            'user_id' => $user->id,
            'payment_reference' => 'DATA_' . strtoupper(uniqid()),
            'bill_reference' => $status ? 'TXN_' . rand(1000000000, 9999999999) : null,
            'amount' => $amount,
            'cashback' => $status ? $cashback : 0,
            'type' => 'Data Services',
            'unique_element' => $dataNumber->phone_number,
            'product_name' => $this->getDataProductName($dataNumber->telco),
            'status' => $status,
            'vtp_request_id' => 'REQ_' . rand(100000000, 999999999),
            'vtp_status' => $status ? VTPEnums::SUCCESS : VTPEnums::FAILED,
            'provider' => 'vtpass',
            'created_at' => $transactionDate,
            'updated_at' => $transactionDate,
        ]);

        return 1;
    }

    private function createTvTransaction($user, $transactionDate, $index): int
    {
        $tvProviders = ['dstv', 'gotv', 'startimes', 'showmax'];
        $provider = $tvProviders[rand(0, count($tvProviders) - 1)];
        
        $tvPlans = [
            'dstv' => [
                ['amount' => 2100, 'plan' => 'DStv Padi'],
                ['amount' => 4150, 'plan' => 'DStv Yanga'],
                ['amount' => 6800, 'plan' => 'DStv Confam'],
                ['amount' => 10500, 'plan' => 'DStv Compact'],
            ],
            'gotv' => [
                ['amount' => 1540, 'plan' => 'GOtv Smallie'],
                ['amount' => 2700, 'plan' => 'GOtv Jinja'],
                ['amount' => 4850, 'plan' => 'GOtv Jolli'],
                ['amount' => 6200, 'plan' => 'GOtv Max'],
            ],
            'startimes' => [
                ['amount' => 1100, 'plan' => 'Startimes Nova'],
                ['amount' => 1500, 'plan' => 'Startimes Basic'],
                ['amount' => 2500, 'plan' => 'Startimes Smart'],
                ['amount' => 4200, 'plan' => 'Startimes Classic'],
            ],
            'showmax' => [
                ['amount' => 1450, 'plan' => 'Showmax Mobile'],
                ['amount' => 2900, 'plan' => 'Showmax Standard'],
                ['amount' => 3200, 'plan' => 'Showmax Pro'],
            ],
        ];

        $plans = $tvPlans[$provider];
        $plan = $plans[rand(0, count($plans) - 1)];
        $amount = $plan['amount'];
        $cashback = $amount * 0.015; // 1.5% cashback for TV
        
        $status = rand(1, 100) <= 94; // 94% success rate for TV
        $smartCardNumber = str_pad(rand(1000000000, 9999999999), 10, '0');

        MullaUserTransactions::create([
            'user_id' => $user->id,
            'payment_reference' => 'TV_' . strtoupper(uniqid()),
            'bill_reference' => $status ? 'TXN_' . rand(1000000000, 9999999999) : null,
            'amount' => $amount,
            'cashback' => $status ? $cashback : 0,
            'type' => 'TV Subscription',
            'unique_element' => $smartCardNumber,
            'product_name' => $this->getTvProductName($provider, $plan['plan']),
            'voucher_code' => $status && $provider === 'showmax' ? $this->generateShowmaxVoucher() : null,
            'status' => $status,
            'vtp_request_id' => 'REQ_' . rand(100000000, 999999999),
            'vtp_status' => $status ? VTPEnums::SUCCESS : VTPEnums::FAILED,
            'provider' => 'vtpass',
            'created_at' => $transactionDate,
            'updated_at' => $transactionDate,
        ]);

        return 1;
    }

    private function getWeightedRandomType($weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($weights as $type => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $type;
            }
        }
        
        return array_key_first($weights);
    }

    /**
     * Get accurate electricity product name based on disco and provider
     */
    private function getElectricityProductName($disco, $provider): string
    {
        if ($provider === 'safehaven') {
            return $disco . ' Electric (SafeHaven)';
        }

        // VTPass product names from responses.txt
        $discoNames = [
            'ABUJA' => 'Abuja Electricity Distribution Company- AEDC',
            'EKEDC' => 'Eko Electric Payment - EKEDC', 
            'IKEDC' => 'Ikeja Electric Payment - IKEDC',
            'IBEDC' => 'IBEDC - Ibadan Electricity Distribution Company',
            'JED' => 'Jos Electric - JED',
            'KAEDCO' => 'Kaduna Electric - KAEDCO',
            'EEDC' => 'Enugu Electric - EEDC',
            'BEDC' => 'Benin Electricity - BEDC',
            'ABEDC' => 'Aba Electric Payment - ABEDC',
            'YEDC' => 'Yola Electric Disco Payment - YEDC',
            'PHED' => 'PHED - Port Harcourt Electric',
        ];

        return $discoNames[$disco] ?? $disco . ' Electric';
    }

    /**
     * Get accurate airtime product name based on telco
     */
    private function getAirtimeProductName($telco): string
    {
        $telcoNames = [
            'mtn' => 'MTN Airtime VTU',
            'glo' => 'GLO Airtime VTU', 
            'airtel' => 'Airtel Airtime VTU',
            'etisalat' => '9mobile Airtime VTU',
        ];

        return $telcoNames[strtolower($telco)] ?? strtoupper($telco) . ' Airtime VTU';
    }

    /**
     * Get accurate data product name based on telco
     */
    private function getDataProductName($telco): string
    {
        $telcoNames = [
            'mtn' => 'MTN Data',
            'glo' => 'GLO Data',
            'airtel' => 'Airtel Data', 
            'etisalat' => '9mobile Data',
        ];

        return $telcoNames[strtolower($telco)] ?? strtoupper($telco) . ' Data';
    }

    /**
     * Get accurate TV product name based on provider
     */
    private function getTvProductName($provider, $plan): string
    {
        $providerNames = [
            'dstv' => 'DSTV Subscription',
            'gotv' => 'GOtv Subscription', 
            'startimes' => 'Startimes Subscription',
            'showmax' => 'ShowMax',
        ];

        return $providerNames[strtolower($provider)] ?? ucfirst($provider) . ' Subscription';
    }

    /**
     * Generate realistic electricity token based on responses.txt patterns
     */
    private function generateElectricityToken(): string
    {
        $patterns = [
            // AEDC pattern: 47133458396693522090
            $this->generateLargeNumber(20),
            // EKEDC pattern: 11786621902768210244  
            $this->generateLargeNumber(20),
            // IKEDC pattern: 26362054405982757802
            $this->generateLargeNumber(20),
            // JED pattern: 3737-6908-5436-2208-2124
            rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
            // IBEDC pattern: 2821 4114 9170 6793 0943
            rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999),
            // KAEDCO pattern: 20229412358945840218
            $this->generateLargeNumber(20),
            // SafeHaven pattern: 6198-6055-9155-8727-1346
            rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
        ];

        return $patterns[array_rand($patterns)];
    }

    /**
     * Generate a large number as string to avoid integer overflow
     */
    private function generateLargeNumber(int $digits): string
    {
        $number = '';
        for ($i = 0; $i < $digits; $i++) {
            $number .= rand(0, 9);
        }
        // Ensure first digit is not 0
        if ($number[0] === '0') {
            $number[0] = rand(1, 9);
        }
        return $number;
    }

    /**
     * Generate Showmax voucher code based on responses.txt pattern
     */
    private function generateShowmaxVoucher(): string
    {
        // Pattern: SHMVHXQ9L3RXGPU
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return 'SHM' . substr(str_shuffle($chars), 0, 12);
    }
}