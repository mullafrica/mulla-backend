<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'firstname' => 'Alex',
                'lastname' => 'Adeiza',
                'email' => 'pikeconcept@gmail.com',
                'phone' => '08136055184',
                'password' => Hash::make('adeiza@1'),
                'email_verified' => true,
                'status' => true,
            ],
            [
                'firstname' => 'Fatima',
                'lastname' => 'Yusuf',
                'email' => 'fatima@test.com',
                'phone' => '08098765432',
                'password' => Hash::make('password123'),
                'email_verified' => true,
                'status' => true,
            ],
            [
                'firstname' => 'Chinedu',
                'lastname' => 'Okafor',
                'email' => 'chinedu@test.com',
                'phone' => '07011111111',
                'password' => Hash::make('password123'),
                'email_verified' => true,
                'status' => true,
            ],
            [
                'firstname' => 'Kemi',
                'lastname' => 'Adebayo',
                'email' => 'kemi@test.com',
                'phone' => '09022222222',
                'password' => Hash::make('password123'),
                'email_verified' => true,
                'status' => true,
            ],
            [
                'firstname' => 'Ibrahim',
                'lastname' => 'Musa',
                'email' => 'ibrahim@test.com',
                'phone' => '08133333333',
                'password' => Hash::make('password123'),
                'email_verified' => true,
                'status' => true,
            ],
            [
                'firstname' => 'Grace',
                'lastname' => 'Okoro',
                'email' => 'grace@test.com',
                'phone' => '07044444444',
                'password' => Hash::make('password123'),
                'email_verified' => false,
                'status' => false,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['phone' => $userData['phone']],
                $userData
            );
        }

        $this->command->info('✅ Created ' . count($users) . ' user accounts');
    }
}