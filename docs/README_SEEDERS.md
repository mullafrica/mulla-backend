# Mulla Database Seeders

This document explains how to use the comprehensive database seeders created for local development and testing.

## 🚀 Quick Start

To seed your local database with realistic test data:

```bash
# Run all seeders
php artisan db:seed

# Or run specific seeders
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=BusinessAccountSeeder
```

## 📊 What Gets Created

### 👥 User Accounts (6 users)
- **Ahmed Abdullahi** - Phone: `08012345678`, Password: `password123`
- **Fatima Yusuf** - Phone: `08098765432`, Password: `password123`  
- **Chinedu Okafor** - Phone: `07011111111`, Password: `password123`
- **Kemi Adebayo** - Phone: `09022222222`, Password: `password123`
- **Ibrahim Musa** - Phone: `08133333333`, Password: `password123`
- **Grace Okoro** - Phone: `07044444444`, Password: `password123` (unverified)

### 🏢 Business Accounts (3 businesses)
- **Tech Solutions Ltd** - Email: `business@test.com`, Password: `password123`
- **Digital Startup Inc** - Email: `startup@test.com`, Password: `password123`
- **Innovation Hub** - Email: `innovation@test.com`, Password: `password123`

### 💰 Financial Data
- **Wallets**: Each user gets a main wallet with realistic balances (₦5k - ₦100k)
- **Cashback Wallets**: Smaller balances representing earned cashbacks
- **Virtual Accounts**: Wema Bank virtual accounts for funding wallets
- **Bank Accounts**: Realistic Nigerian bank account details

### 📱 Service Data (Per User)
- **Meter Numbers**: 1-2 electricity meter numbers across different DISCOs
- **Airtime Numbers**: 2-3 phone numbers for different telcos (MTN, GLO, Airtel, 9Mobile)
- **TV Subscriptions**: DStv, GOtv, Startimes, Showmax smart card numbers
- **Data Numbers**: Phone numbers for data purchases
- **Beneficiaries**: 3-5 transfer beneficiaries with Nigerian bank accounts

### 💳 Transaction History (Per User)
- **5-15 transactions** spread over the last 30 days
- **Transaction Types**:
  - Electricity bills (40% of transactions)
  - Airtime recharge (30% of transactions)
  - Data bundles (20% of transactions)  
  - TV subscriptions (10% of transactions)
- **Realistic amounts and cashbacks**
- **95-98% success rate** (some failed transactions for testing)

### 🏢 Business Operations
- **Transfer Lists**: Employee salaries, vendor payments, contractor fees
- **Bulk Transfers**: Realistic business transfer batches
- **Transfer Transactions**: Individual payments within bulk transfers
- **5-15 recipients per list** with Nigerian names and bank accounts

### 🔐 Authentication Tokens
- **Email verification tokens** for testing registration flow
- **Phone verification tokens** for WhatsApp verification
- **Password reset tokens** for testing forgot password flow

## 📋 Test Scenarios You Can Run

### Personal User Login & Transactions
```bash
# Test login
Phone: 08012345678
Password: password123

# This user has:
# - ₦50,000 main wallet balance
# - ₦2,500 cashback balance
# - Multiple saved meter numbers
# - Transaction history with electricity, airtime, data purchases
```

### Business Account Testing
```bash
# Test business login
Email: business@test.com  
Password: password123

# This business has:
# - Bank account details
# - Transfer lists with employees/vendors
# - Bulk transfer history
# - Multiple completed and pending transfers
```

### Failed Login Testing
```bash
# Test non-existent user (will trigger the Discord log you fixed)
Phone: 08000000000
Password: anything

# Test wrong password
Phone: 08012345678
Password: wrongpassword
```

### Transaction Testing
- Users have realistic transaction history
- Test the transaction endpoints with existing meter numbers
- Test cashback calculations
- Test failed transaction scenarios

### Transfer Testing  
- Businesses have pre-populated beneficiary lists
- Test bulk transfer creation
- Test individual transfer transactions

## 🔄 Re-seeding

To refresh all data:

```bash
# Fresh migration and seed
php artisan migrate:fresh --seed

# Or just truncate and re-seed
php artisan db:seed --class=DatabaseSeeder
```

## 📁 Seeder Files

The seeders are organized by dependency order:

1. **BusinessAccountSeeder** - Independent business accounts
2. **UserSeeder** - Personal user accounts  
3. **UserWalletSeeder** - Wallets and virtual accounts
4. **UserBankAccountSeeder** - Bank accounts and IP tracking
5. **BusinessBankAccountSeeder** - Business bank accounts
6. **UserServiceDataSeeder** - Meter numbers, phone numbers, TV cards
7. **UserBeneficiarySeeder** - Transfer beneficiaries
8. **UserTransactionSeeder** - Transaction history
9. **BusinessTransferSeeder** - Business bulk transfers
10. **AuthTokenSeeder** - Authentication tokens for testing

## 🎯 Key Features

- **Realistic Data**: Nigerian names, phone numbers, addresses, bank accounts
- **Proper Relationships**: All foreign keys and relationships maintained
- **Transaction Variety**: Different bill types, amounts, success/failure rates
- **Time Distribution**: Transactions spread over last 30 days
- **Balance Consistency**: Wallet balances reflect transaction history
- **Provider Diversity**: Multiple telcos, DISCOs, banks represented
- **Status Variety**: Mix of completed, pending, and failed transactions

## 🔍 Monitoring

With the enhanced Discord logging you fixed, you'll now see detailed information when testing failed logins:

```
🔒❌ LOGIN FAILED - User Not Found

Details:
Phone: 08000000000
User Exists: NO
Reason: Account not found  
Ip Address: 127.0.0.1
User Agent: Mozilla/5.0...
Browser: Chrome
Platform: macOS
Location: Unknown
Timestamp: 2024-06-24 10:30:45
```

The seeders provide comprehensive test data that mirrors real-world usage patterns and edge cases for thorough local development and testing.