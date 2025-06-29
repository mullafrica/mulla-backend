<?php

namespace Database\Seeders;

use App\Models\Business\MullaBusinessAccountsModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BusinessAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = [
            [
                'business_name' => 'Tech Solutions Ltd',
                'rc_number' => 'RC1234567',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'business@test.com',
                'phone' => '08033333333',
                'password' => Hash::make('password123'),
            ],
            [
                'business_name' => 'Digital Startup Inc',
                'rc_number' => 'RC2345678',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'email' => 'startup@test.com',
                'phone' => '08044444444',
                'password' => Hash::make('password123'),
            ],
            [
                'business_name' => 'Innovation Hub',
                'rc_number' => 'RC3456789',
                'firstname' => 'Michael',
                'lastname' => 'Johnson',
                'email' => 'innovation@test.com',
                'phone' => '08055555555',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($businesses as $business) {
            MullaBusinessAccountsModel::updateOrCreate(
                ['email' => $business['email']],
                $business
            );
        }

        $this->command->info('✅ Created ' . count($businesses) . ' business accounts');
    }
}