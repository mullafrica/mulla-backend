<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MullaUserTransferBeneficiariesModel;
use Illuminate\Database\Seeder;

class UserBeneficiarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $beneficiaryCount = 0;

        $banks = [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '033', 'name' => 'United Bank For Africa'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '214', 'name' => 'First City Monument Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '032', 'name' => 'Union Bank of Nigeria'],
        ];

        $nigerianNames = [
            ['firstname' => 'Adebayo', 'lastname' => 'Ogundimu'],
            ['firstname' => 'Chioma', 'lastname' => 'Nwankwo'],
            ['firstname' => 'Musa', 'lastname' => 'Ibrahim'],
            ['firstname' => 'Blessing', 'lastname' => 'Okoro'],
            ['firstname' => 'Yakubu', 'lastname' => 'Suleiman'],
            ['firstname' => 'Funmi', 'lastname' => 'Adebola'],
            ['firstname' => 'Emeka', 'lastname' => 'Eze'],
            ['firstname' => 'Halima', 'lastname' => 'Abdullahi'],
            ['firstname' => 'Segun', 'lastname' => 'Afolabi'],
            ['firstname' => 'Ngozi', 'lastname' => 'Okafor'],
            ['firstname' => 'Bello', 'lastname' => 'Ahmad'],
            ['firstname' => 'Sola', 'lastname' => 'Olaniyi'],
            ['firstname' => 'Khadijah', 'lastname' => 'Yusuf'],
            ['firstname' => 'Tunde', 'lastname' => 'Bakare'],
            ['firstname' => 'Amina', 'lastname' => 'Garba'],
        ];

        foreach ($users as $user) {
            // Create 3-5 beneficiaries per user
            $beneficiariesToCreate = rand(3, 5);
            
            for ($i = 0; $i < $beneficiariesToCreate; $i++) {
                $name = $nigerianNames[array_rand($nigerianNames)];
                $bank = $banks[array_rand($banks)];
                $accountNumber = '10' . str_pad(rand(10000000, 99999999), 8, '0');
                
                $fullName = $name['firstname'] . ' ' . $name['lastname'];
                
                MullaUserTransferBeneficiariesModel::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'number' => $accountNumber
                    ],
                    [
                        'name' => strtoupper($fullName),
                        'bank' => $bank['name'],
                    ]
                );
                $beneficiaryCount++;
            }
        }

        $this->command->info("✅ Created {$beneficiaryCount} transfer beneficiaries");
    }

    private function generateNickname($firstname): string
    {
        $relationships = [
            'Family',
            'Friend', 
            'Colleague',
            'Business Partner',
            'Supplier',
            'Contractor',
            'Cousin',
            'Sibling',
            'Parent',
            'Uncle/Aunt'
        ];
        
        $relationship = $relationships[array_rand($relationships)];
        return $firstname . ' (' . $relationship . ')';
    }
}