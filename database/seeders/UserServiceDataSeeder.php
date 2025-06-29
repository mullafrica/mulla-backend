<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MullaUserMeterNumbers;
use App\Models\MullaUserAirtimeNumbers;
use App\Models\MullaUserTvCardNumbers;
use App\Models\MullaUserInternetDataNumbers;
use Illuminate\Database\Seeder;

class UserServiceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $meterCount = 0;
        $airtimeCount = 0;
        $tvCount = 0;
        $dataCount = 0;

        $discos = [
            'ikeja-electric',
            'eko-electric', 
            'abuja-electric',
            'ibadan-electric',
            'jos-electric',
            'kaduna-electric',
            'kano-electric',
            'portharcourt-electric',
            'enugu-electric',
            'benin-electric'
        ];

        $telcos = ['mtn', 'glo', 'airtel', 'etisalat'];
        $tvProviders = ['dstv', 'gotv', 'startimes', 'showmax'];

        foreach ($users as $index => $user) {
            // Create 1-2 meter numbers per user
            $meterCount += $this->createMeterNumbers($user, $discos, $index);
            
            // Create 2-3 airtime numbers per user
            $airtimeCount += $this->createAirtimeNumbers($user, $telcos, $index);
            
            // Create 1 TV subscription per user (not all users)
            if ($index % 2 === 0) {
                $tvCount += $this->createTvCardNumbers($user, $tvProviders, $index);
            }
            
            // Create 1-2 data numbers per user
            $dataCount += $this->createDataNumbers($user, $telcos, $index);
        }

        $this->command->info("✅ Created {$meterCount} meter numbers, {$airtimeCount} airtime numbers, {$tvCount} TV cards, {$dataCount} data numbers");
    }

    private function createMeterNumbers($user, $discos, $index): int
    {
        $count = 0;
        $metersToCreate = rand(1, 2);
        
        for ($i = 0; $i < $metersToCreate; $i++) {
            $disco = $discos[$index % count($discos)];
            $meterNumber = $this->generateMeterNumber();
            
            MullaUserMeterNumbers::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'meter_number' => $meterNumber
                ],
                [
                    'name' => strtoupper($user->firstname . ' ' . $user->lastname),
                    'address' => $this->generateAddress($index),
                    'meter_type' => ['PREPAID', 'POSTPAID'][rand(0, 1)],
                    'disco' => $disco,
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function createAirtimeNumbers($user, $telcos, $index): int
    {
        $count = 0;
        $numbersToCreate = rand(2, 3);
        
        for ($i = 0; $i < $numbersToCreate; $i++) {
            $telco = $telcos[$i % count($telcos)];
            $phoneNumber = $this->generatePhoneNumber($telco, $i);
            
            MullaUserAirtimeNumbers::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber
                ],
                [
                    'telco' => $telco,
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function createTvCardNumbers($user, $tvProviders, $index): int
    {
        $provider = $tvProviders[$index % count($tvProviders)];
        $smartCardNumber = $this->generateSmartCardNumber($provider);
        
        MullaUserTvCardNumbers::updateOrCreate(
            [
                'user_id' => $user->id,
                'card_number' => $smartCardNumber
            ],
            [
                'name' => strtoupper($user->firstname . ' ' . $user->lastname),
                'type' => ['DStv Compact', 'GOtv Max', 'Startimes Classic', 'Showmax Pro'][rand(0, 3)],
            ]
        );
        
        return 1;
    }

    private function createDataNumbers($user, $telcos, $index): int
    {
        $count = 0;
        $numbersToCreate = rand(1, 2);
        
        for ($i = 0; $i < $numbersToCreate; $i++) {
            $telco = $telcos[$i % count($telcos)];
            $phoneNumber = $this->generatePhoneNumber($telco, $i);
            
            MullaUserInternetDataNumbers::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'phone_number' => $phoneNumber
                ],
                [
                    'telco' => $telco . '-data',
                ]
            );
            $count++;
        }
        
        return $count;
    }

    private function generateMeterNumber(): string
    {
        return rand(10000000000, 99999999999); // 11-digit meter number
    }

    private function generatePhoneNumber($telco, $index): string
    {
        $prefixes = [
            'mtn' => ['0803', '0806', '0813', '0810'],
            'glo' => ['0805', '0807', '0815', '0811'],
            'airtel' => ['0802', '0808', '0812', '0701'],
            'etisalat' => ['0809', '0817', '0818', '0909']
        ];
        
        $prefix = $prefixes[$telco][rand(0, count($prefixes[$telco]) - 1)];
        return $prefix . str_pad(rand(1000000, 9999999), 7, '0');
    }

    private function generateSmartCardNumber($provider): string
    {
        $lengths = [
            'dstv' => 10,
            'gotv' => 10,
            'startimes' => 11,
            'showmax' => 12
        ];
        
        $length = $lengths[$provider];
        return str_pad(rand(1000000000, 9999999999), $length, '0');
    }

    private function generateAddress($index): string
    {
        $streets = [
            'Victoria Island',
            'Ikeja GRA',
            'Lekki Phase 1',
            'Surulere',
            'Yaba',
            'Ikoyi',
            'Mainland',
            'Ajah',
            'Gbagada',
            'Isolo'
        ];
        
        $numbers = rand(1, 999);
        $street = $streets[$index % count($streets)];
        
        return "{$numbers} {$street}, Lagos State";
    }
}