<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MullaUserWallets;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserMeterNumbers;
use App\Models\MullaUserCashbackWallets;
use App\Jobs\DiscordBots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Test class for MullaBillController
 * 
 * This class tests all the endpoints in the MullaBillController to ensure they work correctly.
 * We use PHPUnit testing framework which comes with Laravel.
 */
class MullaBillControllerTest extends TestCase
{
    use RefreshDatabase; // This trait recreates the database for each test to ensure clean state
    use WithFaker; // This trait provides access to Faker library for generating fake data

    protected $user; // Property to store our test user

    /**
     * This method runs before each test method
     * We use it to set up common test data that multiple tests need
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the Queue to prevent Discord API calls during testing
        Queue::fake();
        
        // Create a test user using the User factory
        // Factories are defined in database/factories and help create test data
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '08123456789',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'status' => true // Ensure user account is active
        ]);

        // Create a wallet for our test user with some balance
        MullaUserWallets::create([
            'user_id' => $this->user->id,
            'balance' => 5000 // ₦5,000 stored directly in Naira
        ]);

        // Create a cashback wallet for the user
        MullaUserCashbackWallets::create([
            'user_id' => $this->user->id,
            'balance' => 0
        ]);
    }

    /**
     * Test that authenticated users can get VTPass operator products
     * 
     * This test checks the /comet/supported/ops endpoint
     */
    public function test_authenticated_user_can_get_vtpass_operator_products()
    {
        // Authenticate our test user using Sanctum (Laravel's authentication system)
        Sanctum::actingAs($this->user);

        // Mock the external API call to VTPass
        // We don't want to make real API calls during testing, so we fake the response
        Http::fake([
            '*vtpass.com*' => Http::response([
                'content' => [
                    [
                        'serviceID' => 'ikeja-electric',
                        'name' => 'Ikeja Electric',
                        'minimium_amount' => 1000,
                        'maximum_amount' => 50000
                    ]
                ]
            ], 200)
        ]);

        // Make a GET request to our endpoint
        $response = $this->getJson('/v1/comet/supported/ops?bill=electricity');

        // Assert that the request was successful (HTTP 200)
        $response->assertStatus(200);
        
        // Assert that the response contains the expected data structure
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id', // Should be renamed from serviceID
                    'name'
                ]
            ]
        ]);
    }

    /**
     * Test that unauthenticated users cannot access protected endpoints
     */
    public function test_unauthenticated_user_cannot_access_protected_endpoints()
    {
        // Don't authenticate the user for this test
        
        // Try to access a protected endpoint
        $response = $this->getJson('/v1/comet/supported/ops?bill=electricity');

        // Should return 401 Unauthorized
        $response->assertStatus(401);
    }

    /**
     * Test meter validation with valid data
     */
    public function test_can_validate_meter_with_valid_data()
    {
        Sanctum::actingAs($this->user);

        // Mock the VTPass meter validation API
        Http::fake([
            '*vtpass.com*' => Http::response([
                'content' => [
                    'Customer_Name' => 'John Doe',
                    'Address' => '123 Test Street, Lagos',
                    'Meter_Type' => 'PREPAID'
                ]
            ], 200)
        ]);

        // Test data for meter validation
        $meterData = [
            'meter_type' => 'prepaid',
            'bill' => 'electricity',
            'device_number' => '12345678901'
        ];

        // Make request to validate meter endpoint
        $response = $this->getJson('/v1/comet/meter/validate/ikeja-electric?' . http_build_query($meterData));

        // Assert successful response
        $response->assertStatus(200);
        
        // Assert response contains customer data
        $response->assertJsonStructure([
            'data' => [
                'name',
                'address'
            ]
        ]);

        // Assert that meter was saved to database
        $this->assertDatabaseHas('mulla_user_meter_numbers', [
            'user_id' => $this->user->id,
            'meter_number' => '12345678901',
            'name' => 'John Doe'
        ]);
    }

    /**
     * Test meter validation with invalid data
     */
    public function test_meter_validation_fails_with_invalid_data()
    {
        Sanctum::actingAs($this->user);

        // Test with missing required fields
        $response = $this->getJson('/v1/comet/meter/validate/ikeja-electric');

        // Should return validation error
        $response->assertStatus(422); // Unprocessable Entity (validation failed)
    }

    /**
     * Test successful bill payment with VTPass
     * This is the main payment flow test
     */
    public function test_successful_bill_payment_with_vtpass()
    {
        Sanctum::actingAs($this->user);

        // Create a pending transaction first (this simulates the frontend creating a payment reference)
        $paymentReference = 'TEST_' . time();
        
        // Mock successful VTPass payment response
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'response_description' => 'TRANSACTION SUCCESSFUL',
                'requestId' => 'req_123456789',
                'content' => [
                    'transactions' => [
                        'transactionId' => 'vtpass_txn_123',
                        'type' => 'electricity',
                        'product_name' => 'Ikeja Electric',
                        'unique_element' => '12345678901'
                    ]
                ],
                'Token' => '1234-5678-9012-3456-7890',
                'Units' => '15.5',
                'Tax' => '75.50'
            ], 200)
        ]);

        // Payment request data
        $paymentData = [
            'payment_reference' => $paymentReference,
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 2000, // ₦2,000
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        // Make payment request
        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Assert successful payment
        $response->assertStatus(200);

        // Check that transaction was created in database
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentReference,
            'amount' => 2000,
            'status' => true,
            'provider' => 'vtpass'
        ]);

        // Check that wallet balance was reduced (but includes cashback)
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $this->assertLessThan(5000, $wallet->balance); // Balance should be reduced
        $this->assertGreaterThan(3000, $wallet->balance); // But not by the full amount due to cashback

        // Check that cashback was credited
        $cashbackWallet = MullaUserCashbackWallets::where('user_id', $this->user->id)->first();
        $this->assertGreaterThan(0, $cashbackWallet->balance);
    }

    /**
     * Test electricity payment automatic fallback: VTPass fails -> SafeHaven succeeds
     * This tests our automatic fallback mechanism for electricity in a single request
     */
    public function test_electricity_payment_two_stage_failure_handling()
    {
        Sanctum::actingAs($this->user);

        $paymentReference = 'TEST_TWO_STAGE_' . time();
        $originalBalance = 5000; // User's original wallet balance

        $paymentData = [
            'payment_reference' => $paymentReference,
            'serviceID' => 'ikeja-electric', // This is an electricity service
            'billersCode' => '12345678901',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        // Mock VTPass failure + SafeHaven success in one request
        Http::fake([
            // VTPass fails
            '*vtpass.com*' => Http::response([
                'code' => '016',
                'response_description' => 'TRANSACTION FAILED',
                'requestId' => 'vtpass_req_123'
            ], 400),
            
            // SafeHaven succeeds
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_access_token_123',
                'token_type' => 'Bearer',
                'expires_in' => 2399
            ], 200),
            
            '*safehavenmfb.com/vas/pay/utility' => Http::response([
                'statusCode' => 200,
                'message' => 'Utility Package purchased successfully.',
                'data' => [
                    'reference' => 'safehaven_ref_123',
                    'status' => 'successful',
                    'amount' => 2000,
                    'id' => 'safehaven_txn_id_123',
                    'utilityToken' => '1234-5678-9012-3456-7890',
                    'metaData' => [
                        'token' => '1234-5678-9012-3456-7890',
                        'units' => 18.5,
                        'tax' => 125.50,
                        'receiptNo' => 'SH123456789',
                        'disco' => 'IKEJA'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should be successful via SafeHaven fallback
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Payment successful via SafeHaven',
            'provider' => 'safehaven'
        ]);

        // Check transaction was saved as successful with SafeHaven
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentReference,
            'provider' => 'safehaven',
            'vtp_status' => \App\Enums\VTPEnums::SUCCESS,
            'status' => true
        ]);

        // Check that wallet balance was deducted and cashback added
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $expectedBalance = $originalBalance - 2000 + (2000 * 0.005); // Amount - payment + cashback
        $this->assertEquals($expectedBalance, $wallet->balance);
    }

    /**
     * Test electricity payment: VTPass fails, then SafeHaven fails, then user gets refunded
     * This happens automatically in a single request now
     */
    public function test_electricity_both_services_failure_refunds_user_on_second_attempt()
    {
        Sanctum::actingAs($this->user);

        $paymentReference = 'TEST_BOTH_FAIL_' . time();
        $originalBalance = 5000; // Our user's wallet balance

        $paymentData = [
            'payment_reference' => $paymentReference,
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        // Mock both services failing in one request
        Http::fake([
            // VTPass fails
            '*vtpass.com*' => Http::response([
                'code' => '016',
                'response_description' => 'TRANSACTION FAILED'
            ], 400),
            
            // SafeHaven token succeeds but payment fails
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_token'
            ], 200),
            
            '*safehavenmfb.com/vas/pay/utility' => Http::response([
                'statusCode' => 400,
                'message' => 'Payment failed'
            ], 400)
        ]);

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should return error and indicate refund
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Payment failed on both services. Your wallet has been refunded.',
            'refunded' => true
        ]);

        // Check that wallet balance was restored
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $this->assertEquals($originalBalance, $wallet->balance);

        // Check transaction was marked as failed with both_failed provider
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentReference,
            'provider' => 'both_failed',
            'vtp_status' => \App\Enums\VTPEnums::FAILED,
            'status' => false
        ]);
    }

    /**
     * Test that non-electricity services still get immediate refund on VTPass failure
     */
    public function test_non_electricity_services_get_immediate_refund()
    {
        Sanctum::actingAs($this->user);

        $paymentReference = 'TEST_AIRTIME_FAIL_' . time();
        $originalBalance = 5000;

        // Mock VTPass failure for airtime
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '001',
                'response_description' => 'TRANSACTION FAILED'
            ], 400)
        ]);

        $paymentData = [
            'payment_reference' => $paymentReference,
            'serviceID' => 'mtn', // This is airtime, not electricity
            'billersCode' => '08123456789',
            'amount' => 1000,
            'fromWallet' => true,
            'recipient' => '08123456789'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should fail immediately with refund (original behavior for non-electricity)
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Service temporarily unavailable. Please try again later.'
        ]);

        // Balance should be refunded immediately
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $this->assertEquals($originalBalance, $wallet->balance);

        // Transaction should be deleted (not saved)
        $this->assertDatabaseMissing('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentReference
        ]);
    }

    /**
     * Test payment validation for negative amounts
     */
    public function test_payment_rejects_negative_amounts()
    {
        Sanctum::actingAs($this->user);

        $paymentData = [
            'payment_reference' => 'TEST_NEGATIVE_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => -1000, // Negative amount
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should fail validation (Laravel validation returns 422)
        $response->assertStatus(422);
    }

    /**
     * Test payment validation for insufficient balance
     */
    public function test_payment_rejects_insufficient_balance()
    {
        Sanctum::actingAs($this->user);

        $paymentData = [
            'payment_reference' => 'TEST_INSUFFICIENT_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 10000, // ₦10,000 (way more than user's ₦5,000 balance)
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should fail due to insufficient balance (but may trigger full failure with refund)
        $response->assertStatus(400);
        // The response could be either insufficient balance or full failure with refund
        $this->assertTrue(
            str_contains($response->json('message'), 'Insufficient wallet balance') ||
            str_contains($response->json('message'), 'Payment failed. Your wallet has been refunded.')
        );
    }

    /**
     * Test duplicate payment reference rejection for completed transactions
     */
    public function test_payment_rejects_duplicate_reference_for_completed_transactions()
    {
        Sanctum::actingAs($this->user);

        $paymentReference = 'DUPLICATE_REF_' . time();

        // Create an existing COMPLETED transaction with this reference
        MullaUserTransactions::create([
            'user_id' => $this->user->id,
            'payment_reference' => $paymentReference,
            'amount' => 1000,
            'status' => 1, // This is a completed transaction (use integer)
            'provider' => 'vtpass',
            'vtp_status' => \App\Enums\VTPEnums::SUCCESS,
            'bill_reference' => 'completed_txn_123'
        ]);

        $paymentData = [
            'payment_reference' => $paymentReference, // Same reference
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should fail due to duplicate reference for completed transaction
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Transaction already completed successfully.'
        ]);
    }

    /**
     * Test that user can retrieve their saved meter numbers
     */
    public function test_user_can_get_saved_meters()
    {
        Sanctum::actingAs($this->user);

        // Create some saved meters for the user
        MullaUserMeterNumbers::create([
            'user_id' => $this->user->id,
            'meter_number' => '12345678901',
            'name' => 'John Doe',
            'address' => '123 Test Street',
            'meter_type' => 'prepaid'
        ]);

        MullaUserMeterNumbers::create([
            'user_id' => $this->user->id,
            'meter_number' => '10987654321',
            'name' => 'Jane Doe',
            'address' => '456 Test Avenue',
            'meter_type' => 'postpaid'
        ]);

        $response = $this->getJson('/v1/comet/user/meters');

        $response->assertStatus(200);
        $response->assertJsonCount(2); // Should return 2 meters
        
        // Check that only this user's meters are returned
        $responseData = $response->json();
        foreach ($responseData as $meter) {
            $this->assertEquals($this->user->id, $meter['user_id']);
        }
    }

    /**
     * Test that payment endpoint exists and is protected by authentication
     * (Rate limiting is complex to test and depends on Redis/cache setup)
     */
    public function test_payment_endpoint_requires_authentication()
    {
        // Don't authenticate - test that endpoint requires authentication
        $paymentData = [
            'payment_reference' => 'TEST_UNAUTH_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 1000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should require authentication
        $response->assertStatus(401);
    }

    /**
     * Test meter validation with VTPass failure triggers SafeHaven fallback
     */
    public function test_meter_validation_vtpass_failure_triggers_safehaven_fallback()
    {
        Sanctum::actingAs($this->user);

        // Mock VTPass meter validation failure (WrongBillersCode = true)
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'content' => [
                    'error' => 'This meter is not correct or is not a valid Abuja prepaid meter. Please check and try again',
                    'WrongBillersCode' => true
                ]
            ], 200),
            
            // Mock SafeHaven OAuth token
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_safehaven_token_123',
                'token_type' => 'Bearer',
                'expires_in' => 2399
            ], 200),
            
            // Mock SafeHaven meter validation success
            '*safehavenmfb.com/vas/verify' => Http::response([
                'statusCode' => 200,
                'message' => 'Power Data verified successfully.',
                'data' => [
                    'discoCode' => 'ABUJA',
                    'vendType' => 'PREPAID',
                    'meterNo' => '45700443695',
                    'name' => 'ASO ESTATE GARDEN',
                    'address' => 'BLOCK 30 HOUSE 3 PLOT 57 KARSANA, , KUBWA'
                ]
            ], 200)
        ]);

        $meterData = [
            'meter_type' => 'prepaid',
            'bill' => 'electricity',
            'device_number' => '45700443695'
        ];

        $response = $this->getJson('/v1/comet/meter/validate/abuja-electric?' . http_build_query($meterData));

        // Should be successful via SafeHaven
        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'data' => [
                'name',
                'address',
                'validation_provider'
            ]
        ]);

        $response->assertJson([
            'data' => [
                'validation_provider' => 'safehaven'
            ]
        ]);

        // Assert meter was saved with SafeHaven as validation provider
        $this->assertDatabaseHas('mulla_user_meter_numbers', [
            'user_id' => $this->user->id,
            'meter_number' => '45700443695',
            'name' => 'ASO ESTATE GARDEN',
            'validation_provider' => 'safehaven'
        ]);
    }

    /**
     * Test meter validation fails on both VTPass and SafeHaven
     */
    public function test_meter_validation_fails_on_both_services()
    {
        Sanctum::actingAs($this->user);

        // Mock VTPass failure
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'content' => [
                    'error' => 'Invalid meter number',
                    'WrongBillersCode' => true
                ]
            ], 200),
            
            // Mock SafeHaven OAuth token success
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_token'
            ], 200),
            
            // Mock SafeHaven validation failure
            '*safehavenmfb.com/vas/verify' => Http::response([
                'statusCode' => 400,
                'message' => 'Failed to verify Power Data. Please try again.'
            ], 400)
        ]);

        $meterData = [
            'meter_type' => 'prepaid',
            'bill' => 'electricity',
            'device_number' => '45700443695'
        ];

        $response = $this->getJson('/v1/comet/meter/validate/abuja-electric?' . http_build_query($meterData));

        // Should fail with appropriate error message
        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Unable to validate meter number with any service. Please check the meter number and try again.',
            'validation_failed' => true
        ]);
    }

    /**
     * Test electricity purchase uses SafeHaven when meter was validated with SafeHaven
     */
    public function test_electricity_purchase_uses_safehaven_when_meter_validated_with_safehaven()
    {
        Sanctum::actingAs($this->user);

        // Create a meter that was validated via SafeHaven
        MullaUserMeterNumbers::create([
            'user_id' => $this->user->id,
            'meter_number' => '45700443695',
            'name' => 'ASO ESTATE GARDEN',
            'address' => 'BLOCK 30 HOUSE 3 PLOT 57 KARSANA',
            'meter_type' => 'PREPAID',
            'disco' => 'ABUJA',
            'validation_provider' => 'safehaven'
        ]);

        // Mock SafeHaven OAuth and payment success (should skip VTPass entirely)
        Http::fake([
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_token'
            ], 200),
            
            '*safehavenmfb.com/vas/pay/utility' => Http::response([
                'statusCode' => 200,
                'message' => 'Utility Package purchased successfully.',
                'data' => [
                    'reference' => 'safehaven_direct_123',
                    'status' => 'successful',
                    'amount' => 2000,
                    'id' => 'safehaven_direct_id',
                    'utilityToken' => '6198-6055-9155-8727-1346',
                    'metaData' => [
                        'token' => '6198-6055-9155-8727-1346',
                        'units' => 4.4,
                        'tax' => 69.77,
                        'disco' => 'ABUJA',
                        'receiptNo' => '15240420'
                    ]
                ]
            ], 200)
        ]);

        $paymentData = [
            'payment_reference' => 'TEST_SAFEHAVEN_DIRECT_' . time(),
            'serviceID' => 'abuja-electric',
            'billersCode' => '45700443695',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should be successful via SafeHaven direct routing
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Payment successful via SafeHaven',
            'provider' => 'safehaven'
        ]);

        // Transaction should be created with SafeHaven provider
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentData['payment_reference'],
            'provider' => 'safehaven',
            'status' => true
        ]);
    }

    /**
     * Test SafeHaven pending transaction handling
     */
    public function test_safehaven_pending_transaction_handling()
    {
        Sanctum::actingAs($this->user);

        // Mock VTPass failure first
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '016',
                'response_description' => 'TRANSACTION FAILED'
            ], 400)
        ]);

        $paymentData = [
            'payment_reference' => 'TEST_SH_PENDING_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        // First attempt - VTPass fails
        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);
        $response->assertStatus(400);

        // Second attempt - SafeHaven returns pending
        Http::fake([
            '*safehavenmfb.com/oauth2/token' => Http::response([
                'access_token' => 'fake_token'
            ], 200),
            
            '*safehavenmfb.com/vas/pay/utility' => Http::response([
                'statusCode' => 200,
                'message' => 'Utility Package processing.',
                'data' => [
                    'reference' => 'safehaven_pending_123',
                    'status' => 'pending',
                    'amount' => 2000,
                    'id' => 'safehaven_txn_123'
                ]
            ], 200)
        ]);

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should return pending response
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Transaction is processing via SafeHaven. Please wait a moment.',
            'pending' => true,
            'provider' => 'safehaven'
        ]);

        // Transaction should be saved as pending with SafeHaven provider
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentData['payment_reference'],
            'provider' => 'safehaven',
            'vtp_status' => \App\Enums\VTPEnums::PENDING,
            'status' => false
        ]);
    }

    /**
     * Test requery functionality with enhanced logging
     */
    public function test_requery_functionality_with_logging()
    {
        Sanctum::actingAs($this->user);

        // Create a pending transaction
        $transaction = MullaUserTransactions::create([
            'user_id' => $this->user->id,
            'payment_reference' => 'TEST_REQUERY_' . time(),
            'amount' => 2000,
            'status' => false,
            'vtp_status' => \App\Enums\VTPEnums::PENDING,
            'vtp_request_id' => 'vtpass_req_12345',
            'provider' => 'vtpass'
        ]);

        // Mock successful requery response
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'response_description' => 'TRANSACTION SUCCESSFUL',
                'Token' => '1234-5678-9012-3456-7890',
                'Units' => '15.5',
                'requestId' => 'vtpass_req_12345',
                'content' => [
                    'transactions' => [
                        'transactionId' => 'vtpass_success_txn',
                        'type' => 'Electricity Bill',
                        'product_name' => 'Ikeja Electric Payment - IKEDC',
                        'unique_element' => '12345678901'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/v1/comet/bill/requery/' . $transaction->id);

        // Should be successful
        $response->assertStatus(200);

        // Transaction should be updated to successful
        $this->assertDatabaseHas('mulla_user_transactions', [
            'id' => $transaction->id,
            'status' => true,
            'vtp_status' => \App\Enums\VTPEnums::SUCCESS,
            'bill_token' => '1234-5678-9012-3456-7890'
        ]);
    }

    /**
     * Test wallet balance operations use correct Naira amounts (not kobo multiplied)
     */
    public function test_wallet_balance_operations_use_naira_amounts()
    {
        Sanctum::actingAs($this->user);

        // Verify initial wallet balance is in Naira
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $this->assertEquals(5000, $wallet->balance); // Should be ₦5,000, not 500,000 kobo

        // Mock successful payment
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'response_description' => 'TRANSACTION SUCCESSFUL',
                'requestId' => 'req_wallet_test',
                'content' => [
                    'transactions' => [
                        'transactionId' => 'txn_wallet_test',
                        'type' => 'Electricity Bill',
                        'product_name' => 'Test Electric',
                        'unique_element' => '12345678901'
                    ]
                ],
                'Token' => '1234-5678-9012',
                'Units' => '10.0'
            ], 200)
        ]);

        $paymentData = [
            'payment_reference' => 'TEST_WALLET_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 1000, // ₦1,000
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);
        $response->assertStatus(200);

        // Check wallet balance after successful payment
        $wallet->refresh();
        
        // Balance should be: 5000 - 1000 + cashback
        // Electricity cashback is 0.5% = 5 Naira
        $expectedBalance = 5000 - 1000 + (1000 * 0.005); // ₦4,005
        $this->assertEquals($expectedBalance, $wallet->balance);

        // Check cashback wallet
        $cashbackWallet = MullaUserCashbackWallets::where('user_id', $this->user->id)->first();
        $this->assertEquals(1000 * 0.005, $cashbackWallet->balance); // ₦5 cashback
    }

    /**
     * Test insufficient balance check uses Naira comparison (not kobo)
     */
    public function test_insufficient_balance_check_uses_naira_comparison()
    {
        Sanctum::actingAs($this->user);

        // User has ₦5,000 balance, try to spend ₦6,000
        $paymentData = [
            'payment_reference' => 'TEST_INSUFFICIENT_NAIRA_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 6000, // More than ₦5,000 balance
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);

        // Should fail due to insufficient balance
        $response->assertStatus(400);
        
        // Message should indicate insufficient balance
        $this->assertTrue(
            str_contains($response->json('message'), 'Insufficient wallet balance')
        );

        // Wallet balance should remain unchanged
        $wallet = MullaUserWallets::where('user_id', $this->user->id)->first();
        $this->assertEquals(5000, $wallet->balance);
    }

    /**
     * Test cashback calculation and crediting works with Naira amounts
     */
    public function test_cashback_calculation_works_with_naira_amounts()
    {
        Sanctum::actingAs($this->user);

        // Test different service types and their cashback rates
        $testCases = [
            [
                'serviceID' => 'ikeja-electric',
                'amount' => 2000,
                'expectedCashbackRate' => 0.005, // 0.5%
                'type' => 'electricity'
            ],
            [
                'serviceID' => 'mtn',
                'amount' => 1000,
                'expectedCashbackRate' => 0.015, // 1.5%
                'type' => 'airtime'
            ]
        ];

        foreach ($testCases as $index => $testCase) {
            $paymentRef = 'TEST_CASHBACK_' . $index . '_' . time();
            
            // Mock successful response
            Http::fake([
                '*vtpass.com*' => Http::response([
                    'code' => '000',
                    'response_description' => 'TRANSACTION SUCCESSFUL',
                    'requestId' => 'req_' . $index,
                    'content' => [
                        'transactions' => [
                            'transactionId' => 'txn_' . $index,
                            'type' => $testCase['type'] === 'electricity' ? 'Electricity Bill' : 'Airtime Recharge',
                            'product_name' => $testCase['type'] === 'electricity' ? 'Test Electric' : 'MTN Airtime',
                            'unique_element' => $testCase['type'] === 'electricity' ? '12345678901' : '08123456789'
                        ]
                    ],
                    'Token' => $testCase['type'] === 'electricity' ? '1234-5678' : null
                ], 200)
            ]);

            $paymentData = [
                'payment_reference' => $paymentRef,
                'serviceID' => $testCase['serviceID'],
                'billersCode' => $testCase['type'] === 'electricity' ? '12345678901' : '08123456789',
                'amount' => $testCase['amount'],
                'fromWallet' => true,
                'variation_code' => $testCase['type'] === 'electricity' ? 'prepaid' : null,
                'meter_type' => $testCase['type'] === 'electricity' ? 'prepaid' : null,
                'recipient' => $testCase['type'] === 'airtime' ? '08123456789' : null
            ];

            $response = $this->postJson('/v1/comet/bill/pay', $paymentData);
            $response->assertStatus(200);

            // Check cashback was calculated correctly
            $expectedCashback = $testCase['amount'] * $testCase['expectedCashbackRate'];
            
            $this->assertDatabaseHas('mulla_user_transactions', [
                'payment_reference' => $paymentRef,
                'cashback' => $expectedCashback,
                'amount' => $testCase['amount']
            ]);
        }
    }

    /**
     * Test transaction types match responses.txt data
     */
    public function test_transaction_types_match_response_data()
    {
        Sanctum::actingAs($this->user);

        // Mock successful VTPass responses for different service types
        Http::fake([
            '*vtpass.com*' => Http::response([
                'code' => '000',
                'response_description' => 'TRANSACTION SUCCESSFUL',
                'requestId' => 'req_123',
                'content' => [
                    'transactions' => [
                        'transactionId' => 'txn_123',
                        'type' => 'Electricity Bill', // This should match our responses.txt
                        'product_name' => 'Ikeja Electric Payment - IKEDC',
                        'unique_element' => '12345678901'
                    ]
                ],
                'Token' => '1234-5678-9012',
                'Units' => '15.5'
            ], 200)
        ]);

        $paymentData = [
            'payment_reference' => 'TEST_TYPE_' . time(),
            'serviceID' => 'ikeja-electric',
            'billersCode' => '12345678901',
            'amount' => 2000,
            'fromWallet' => true,
            'variation_code' => 'prepaid',
            'meter_type' => 'prepaid'
        ];

        $response = $this->postJson('/v1/comet/bill/pay', $paymentData);
        $response->assertStatus(200);

        // Verify transaction type matches responses.txt format
        $this->assertDatabaseHas('mulla_user_transactions', [
            'user_id' => $this->user->id,
            'payment_reference' => $paymentData['payment_reference'],
            'type' => 'Electricity Bill', // Should match VTPass response format
            'product_name' => 'Ikeja Electric Payment - IKEDC',
            'provider' => 'vtpass'
        ]);
    }
}