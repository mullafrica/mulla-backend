<?php

namespace Database\Seeders;

use App\Models\Business\MullaBusinessAccountsModel;
use App\Models\Business\MullaBusinessBankAccountsModel;
use Illuminate\Database\Seeder;

class BusinessBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = MullaBusinessAccountsModel::all();
        $accountCount = 0;

        $banks = [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '033', 'name' => 'United Bank For Africa'],
        ];

        foreach ($businesses as $index => $business) {
            $bank = $banks[$index % count($banks)];
            $accountNumber = '20' . str_pad(rand(10000000, 99999999), 8, '0');
            
            MullaBusinessBankAccountsModel::updateOrCreate(
                ['business_id' => $business->id],
                [
                    'bank' => $bank['name'],
                    'account_number' => $accountNumber,
                    'account_name' => strtoupper($business->business_name),
                ]
            );
            $accountCount++;
        }

        $this->command->info("✅ Created {$accountCount} business bank accounts");
    }
}