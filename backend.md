# Mulla Backend Architecture Documentation

## Overview

The Mulla Backend is a comprehensive Laravel-based fintech API that serves both personal and business users with digital wallet management, bill payments, money transfers, and business bulk payment capabilities. The application follows a clean architecture with service layers, queue-based processing, and extensive third-party integrations.

## Core Architecture

### Framework & Technology Stack
- **Framework**: Laravel 10.x with PHP 8.1+
- **Authentication**: Laravel Sanctum for API token authentication
- **Database**: MySQL with Eloquent ORM
- **Queue System**: Redis/Database-based job queues
- **Caching**: Redis for performance optimization
- **File Storage**: Laravel filesystem with public storage

### Project Structure
```
app/
├── Console/
│   └── Commands/          # Artisan commands (Discord status, daily summary)
├── Enums/                 # Configuration constants (BaseUrls, Cashbacks, etc.)
├── Exceptions/            # Custom exception handlers
├── Http/
│   ├── Controllers/       # API controllers (Personal & Business)
│   ├── Middleware/        # Custom middleware (user status, auth)
│   └── Kernel.php         # HTTP kernel configuration
├── Jobs/                  # Background job processors
├── Mail/                  # Email templates and classes
├── Models/                # Eloquent models
├── Providers/             # Service providers
├── Services/              # Business logic services
└── Traits/                # Reusable traits
```

## Business Domains

### Personal Users
The personal user domain handles individual account management and financial operations:

**Core Features:**
- Digital wallet management with NGN currency
- Bill payments (electricity, airtime, internet data, TV subscriptions)
- Bank transfers with beneficiary management
- Cashback rewards system
- Virtual account creation for wallet funding
- Transaction history and analytics

**Key Controllers:**
- `MullaAuthController` - Authentication and registration
- `MullaTransferController` - Money transfers and beneficiaries
- `MullaBillController` - Bill payment processing
- `WalletController` - Wallet operations and funding
- `MullaTransactionsController` - Transaction history

### Business Users
The business domain provides bulk payment capabilities for corporate clients:

**Core Features:**
- Business account authentication and management
- Bulk transfer list creation and management
- CSV file upload for bulk payments
- Transfer initiation and monitoring
- Business-specific transaction tracking

**Key Controllers:**
- `MullaBusinessAuthController` - Business authentication
- `MullaBusinessBulkTransferController` - Bulk transfer management

## Database Models & Relationships

### Core User Models
- **User**: Main user entity with wallet and cashback wallet attributes
- **MullaUserWallets**: User wallet balances and transaction limits
- **MullaUserCashbackWallets**: Cashback rewards storage and management
- **MullaUserTransactions**: Complete transaction history with caching

### Business Models
- **MullaBusinessAccountsModel**: Business account authentication and profiles
- **MullaBusinessBulkTransfersModel**: Bulk transfer batch management
- **MullaBusinessBulkTransferTransactions**: Individual transfer transactions within batches

### Utility Models
- **MullaUserMeterNumbers**: Saved electricity meter numbers with validation
- **MullaUserAirtimeNumbers**: Saved mobile numbers for quick recharge
- **MullaUserTvCardNumbers**: Saved TV subscription details
- **CustomerVirtualAccountsModel**: Virtual account information for funding

### Authentication Models
- **VerifyEmailToken**: Email verification tokens
- **VerifyPhoneTokenModel**: Phone verification tokens
- **ForgotPasswordTokens**: Password reset tokens

## External Service Integrations

### Payment Providers
1. **Paystack**: Primary payment processor
   - Wallet funding via bank transfers
   - Money transfers to bank accounts
   - Bank account validation
   - Transaction webhooks

2. **SafeHaven**: Secondary payment provider
   - Electricity bill payments
   - Alternative transfer processing
   - Account name verification

3. **VTPass**: Bill payment service
   - Airtime purchases
   - Internet data purchases
   - TV subscription renewals

### Third-Party Services
- **Customer.io**: Customer engagement and email marketing
- **Discord**: Real-time logging and monitoring via webhooks
- **Firebase**: Push notifications through FCM
- **ConvertKit**: Email newsletter management

## Services Layer

### Core Services
- **BulkTransferService**: Handles business bulk transfer operations
- **WalletService**: Manages user wallet operations
- **SafeHavenService**: Integration with SafeHaven payment provider
- **ComplianceService**: Handles BVN validation and compliance checks
- **PushNotification**: Firebase push notification service

### Service Interfaces
- **IBulkTransferService**: Bulk transfer service contract
- **IWalletService**: Wallet service contract
- **ISafeHavenService**: SafeHaven service contract
- **IComplianceService**: Compliance service contract

## Jobs & Queue System

### Job Classes
1. **Jobs.php**: Main job processor handling:
   - Account creation workflows
   - Email sending (welcome, transaction, login alerts)
   - BVN validation processes
   - Customer.io integration
   - Push notifications

2. **DiscordBots.php**: Discord logging with rate limiting
3. **WebhookJobs.php**: Webhook processing from external services
4. **MullaBusinessJobs.php**: Business-specific operations
5. **ProcessDiscordBatch.php**: Batched Discord message processing

### Queue Configuration
- Supports multiple queue drivers (sync, database, redis)
- Rate limiting for Discord messages
- Retry mechanisms for failed jobs

## API Structure

### Authentication Endpoints
```
POST /comet/auth                    # User login
POST /comet/auth/register/web       # Web registration
POST /comet/auth/verify/web         # Email verification
POST /comet/auth/token/phone        # Phone verification
POST /comet/auth/forgot-password    # Password reset
```

### Personal User Endpoints
```
GET  /comet/user                    # User profile
GET  /comet/user/wallets           # Wallet information
POST /comet/bill/pay               # Bill payment processing
POST /comet/transfer/*             # Transfer operations
POST /comet/wallet/*               # Wallet operations
GET  /comet/transactions           # Transaction history
```

### Business Endpoints
```
POST /business/auth/login          # Business authentication
POST /business/bulktransfer        # Create bulk transfer
GET  /business/bulktransfer        # Get bulk transfers
POST /business/bt/transactions/upload # CSV upload for transfers
```

## Authentication & Authorization

### Authentication Methods
- **Laravel Sanctum**: API token authentication
- **Separate Guards**: Personal (`auth:sanctum`) and business (`auth:business`) users
- **Token Management**: Token expiration and refresh handling

### Middleware
- **CheckUserStatus**: Validates user account status and restrictions
- **Rate Limiting**: Protects sensitive endpoints
- **Throttling**: Payment operation restrictions (4 requests per minute)

## Configuration & Environment

### Key Configuration Files
- **config/app.php**: Application settings
- **config/services.php**: External service configurations
- **config/queue.php**: Job queue configuration
- **config/sanctum.php**: Authentication settings

### Environment Variables
- Payment provider API keys (Paystack, SafeHaven, VTPass)
- Database connection settings
- Queue driver configuration
- External service credentials

## Caching Strategy

### Redis Caching
- User statistics caching (24-hour expiration)
- SafeHaven token caching (33-minute expiration)
- Paystack bank list caching (30-day expiration)
- User wallet balance caching

### Cache Keys
- `user_stats_{user_id}`: User transaction statistics
- `safehaven_token`: SafeHaven authentication token
- `pt_banks`: Paystack bank list

## Security Features

### Compliance
- BVN validation integration
- Account name verification
- Transaction monitoring and reporting
- IP address and browser tracking

### Rate Limiting
- Discord message rate limiting service
- API endpoint throttling
- Payment operation restrictions

### Data Protection
- Encrypted sensitive data storage
- Secure token generation
- Password hashing with bcrypt

## Development & Testing

### Testing Structure
- PHPUnit test suite
- Feature tests for controllers
- Unit tests for services
- Testing documentation and checklists

### Development Tools
- Artisan commands for maintenance
- Database seeders for development data
- Migration files for schema management

## Deployment & Monitoring

### Deployment Configuration
- **deployment-config.json**: Deployment settings
- Environment-specific configurations
- Database migration management

### Monitoring
- Discord integration for real-time logging
- Transaction monitoring and alerts
- Error tracking and reporting

## Key Technical Patterns

### Custom Traits
- **Reusables**: Common utility functions
- **UniqueId**: ID generation utilities
- **Defaults**: Default value management

### Enhanced Logging
- Comprehensive Discord integration
- Detailed transaction logging with user context
- IP address and browser tracking

### Error Handling
- Custom exception handlers
- Graceful error responses
- Comprehensive error logging

## Database Schema Insights

### Migration History
- Progressive feature additions from April 2024
- Business features added in May-June 2024
- Recent additions include phone verification and user status management

### Key Relationships
- User → Wallet (1:1)
- User → Transactions (1:Many)
- Business → BulkTransfers (1:Many)
- BulkTransfer → Transactions (1:Many)

## Future Considerations

### Scalability
- Queue system for background processing
- Redis caching for performance
- Database indexing for optimization

### Monitoring
- Real-time transaction monitoring
- Performance metrics tracking
- Error rate monitoring

### Security
- Regular security audits
- Compliance monitoring
- Data encryption reviews

---

This documentation serves as the definitive guide for understanding the Mulla Backend architecture, business logic, and implementation patterns. It should be referenced for all development activities and maintained as the system evolves.