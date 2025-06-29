<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAltBankAccountsModel;
use App\Models\MullaUserIPDetailsModel;
use Illuminate\Database\Seeder;

class UserBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $bankAccountCount = 0;
        $ipDetailCount = 0;

        $banks = [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '014', 'name' => 'Afribank Nigeria Plc'],
            ['code' => '023', 'name' => 'Citibank Nigeria Limited'],
            ['code' => '050', 'name' => 'Ecobank Nigeria Plc'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '214', 'name' => 'First City Monument Bank'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '030', 'name' => 'Heritage Bank'],
            ['code' => '301', 'name' => 'Jaiz Bank'],
            ['code' => '082', 'name' => 'Keystone Bank'],
            ['code' => '014', 'name' => 'MainStreet Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '068', 'name' => 'Standard Chartered Bank'],
            ['code' => '232', 'name' => 'Sterling Bank'],
            ['code' => '033', 'name' => 'United Bank For Africa'],
            ['code' => '032', 'name' => 'Union Bank of Nigeria'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
        ];

        foreach ($users as $index => $user) {
            // Create bank account for each user
            $bank = $banks[$index % count($banks)];
            $accountNumber = '10' . str_pad(rand(10000000, 99999999), 8, '0');
            
            UserAltBankAccountsModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bvn' => '221' . str_pad(rand(10000000, 99999999), 8, '0'),
                    'nuban' => $accountNumber,
                    'bank_code' => $bank['code'],
                    'bank_name' => $bank['name'],
                    'account_name' => strtoupper($user->firstname . ' ' . $user->lastname),
                ]
            );
            $bankAccountCount++;

            // Create IP details for tracking
            $ipAddresses = [
                '197.210.55.123',
                '105.112.14.89',
                '196.216.173.45',
                '102.89.47.234',
                '154.113.16.78',
                '41.58.187.92'
            ];

            $location = json_encode([
                'country' => 'Nigeria',
                'city' => ['Lagos', 'Abuja', 'Kano', 'Port Harcourt', 'Ibadan'][$index % 5],
            ]);

            MullaUserIPDetailsModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ip' => $ipAddresses[$index % count($ipAddresses)],
                    'browser' => ['Chrome', 'Safari', 'Firefox', 'Edge'][$index % 4],
                    'platform' => ['Android', 'iOS', 'Windows', 'MacOS'][$index % 4],
                    'location' => $location,
                ]
            );
            $ipDetailCount++;
        }

        $this->command->info("✅ Created {$bankAccountCount} bank accounts and {$ipDetailCount} IP tracking records");
    }
}