<?php

namespace Database\Seeders;

use App\Models\ForgotPasswordTokens;
use App\Models\VerifyEmailToken;
use App\Models\VerifyPhoneTokenModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuthTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tokenCount = 0;

        // Create a few email verification tokens for testing
        $testEmails = [
            'pending@test.com',
            'verification@test.com', 
            'newuser@test.com'
        ];

        foreach ($testEmails as $email) {
            VerifyEmailToken::create([
                'email' => $email,
                'token' => strtoupper(Str::random(6)),
            ]);
            $tokenCount++;
        }

        // Create a few phone verification tokens for testing
        $testPhones = [
            '08077777777',
            '07088888888',
            '09099999999'
        ];

        foreach ($testPhones as $phone) {
            VerifyPhoneTokenModel::create([
                'phone' => $phone,
                'token' => strtoupper(Str::random(6)),
            ]);
            $tokenCount++;
        }

        // Create a few password reset tokens for existing users
        $users = User::take(2)->get();
        foreach ($users as $user) {
            ForgotPasswordTokens::create([
                'email' => $user->email,
                'phone' => $user->phone,
                'token' => strtoupper(Str::random(8)),
            ]);
            $tokenCount++;
        }

        $this->command->info("✅ Created {$tokenCount} authentication tokens for testing");
        $this->command->info("📱 Test phone verification: 08077777777");
        $this->command->info("📧 Test email verification: pending@test.com");
        $this->command->info("🔐 Password reset tokens created for existing users");
    }
}