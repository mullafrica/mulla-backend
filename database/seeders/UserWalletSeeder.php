<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MullaUserWallets;
use App\Models\MullaUserCashbackWallets;
use App\Models\CustomerVirtualAccountsModel;
use Illuminate\Database\Seeder;

class UserWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $walletCount = 0;
        $cashbackCount = 0;
        $virtualAccountCount = 0;

        foreach ($users as $user) {
            // Create main wallet with realistic balance
            $balance = match($user->phone) {
                '08012345678' => 50000 * 100, // ₦50,000 (stored in kobo)
                '08098765432' => 25000 * 100, // ₦25,000
                '07011111111' => 100000 * 100, // ₦100,000
                '09022222222' => 5000 * 100, // ₦5,000
                '08133333333' => 75000 * 100, // ₦75,000
                default => 10000 * 100, // ₦10,000
            };

            MullaUserWallets::updateOrCreate(
                ['user_id' => $user->id],
                ['balance' => $balance]
            );
            $walletCount++;

            // Create cashback wallet with smaller balance
            $cashbackBalance = match($user->phone) {
                '08012345678' => 2500 * 100, // ₦2,500
                '08098765432' => 1200 * 100, // ₦1,200
                '07011111111' => 5000 * 100, // ₦5,000
                '09022222222' => 250 * 100, // ₦250
                '08133333333' => 3750 * 100, // ₦3,750
                default => 500 * 100, // ₦500
            };

            MullaUserCashbackWallets::updateOrCreate(
                ['user_id' => $user->id],
                ['balance' => $cashbackBalance]
            );
            $cashbackCount++;

            // Create virtual account
            $accountNumber = '90' . str_pad($user->id, 8, '0', STR_PAD_LEFT);
            CustomerVirtualAccountsModel::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'customer_id' => 'CUS_' . strtoupper(uniqid()),
                    'bank_id' => '035',
                    'bank_name' => 'Wema Bank',
                    'bank_slug' => 'wema-bank',
                    'account_name' => $user->firstname . ' ' . $user->lastname,
                    'account_number' => $accountNumber,
                ]
            );
            $virtualAccountCount++;
        }

        $this->command->info("✅ Created {$walletCount} main wallets, {$cashbackCount} cashback wallets, {$virtualAccountCount} virtual accounts");
    }
}