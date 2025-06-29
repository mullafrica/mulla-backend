<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Mulla Database Seeding...');

        // Level 1: Independent models (no dependencies)
        $this->command->info('📊 Seeding independent models...');
        $this->call([
            BusinessAccountSeeder::class,
        ]);

        // Level 2: User accounts (core dependency)
        $this->command->info('👥 Seeding user accounts...');
        $this->call([
            UserSeeder::class,
        ]);

        // Level 3: User-dependent core models
        $this->command->info('💰 Seeding user wallets and accounts...');
        $this->call([
            UserWalletSeeder::class,
            UserBankAccountSeeder::class,
            BusinessBankAccountSeeder::class,
        ]);

        // Level 4: User service data
        $this->command->info('📱 Seeding user service data...');
        $this->call([
            UserServiceDataSeeder::class,
            UserBeneficiarySeeder::class,
        ]);

        // Level 5: Transaction data
        $this->command->info('💳 Seeding transaction data...');
        $this->call([
            UserTransactionSeeder::class,
        ]);

        // Level 6: Business operations
        $this->command->info('🏢 Seeding business operations...');
        $this->call([
            BusinessTransferSeeder::class,
        ]);

        // Level 7: Authentication tokens (for testing)
        $this->command->info('🔐 Seeding authentication tokens...');
        $this->call([
            AuthTokenSeeder::class,
        ]);

        $this->command->info('✅ Mulla Database Seeding Complete!');
        $this->command->info('📝 You can now test with the following credentials:');
        $this->command->line('');
        $this->command->line('👤 Personal Users:');
        $this->command->line('   Phone: 08012345678, Password: password123');
        $this->command->line('   Phone: 08098765432, Password: password123');
        $this->command->line('   Phone: 07011111111, Password: password123');
        $this->command->line('');
        $this->command->line('🏢 Business Users:');
        $this->command->line('   Email: business@test.com, Password: password123');
        $this->command->line('   Email: startup@test.com, Password: password123');
        $this->command->line('');
        $this->command->line('🔗 Virtual accounts, beneficiaries, and transactions are pre-populated');
    }
}
