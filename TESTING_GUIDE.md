# Testing Guide for MullaBillController

## Overview
This guide explains how to run and understand the tests for the MullaBillController.

## Test Types

### 1. Feature Tests (`tests/Feature/MullaBillControllerTest.php`)
- **What they do**: Test complete user flows by making HTTP requests to your API endpoints
- **When to use**: Test how users interact with your API from start to finish
- **Examples**: User login → make payment → check transaction saved

### 2. Unit Tests (`tests/Unit/MullaBillControllerUnitTest.php`)
- **What they do**: Test individual methods in isolation
- **When to use**: Test specific business logic without HTTP requests
- **Examples**: Test if cashback calculation is correct

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Only MullaBillController Tests
```bash
# Run feature tests only
php artisan test tests/Feature/MullaBillControllerTest.php

# Run unit tests only
php artisan test tests/Unit/MullaBillControllerUnitTest.php

# Run both with verbose output
php artisan test tests/Feature/MullaBillControllerTest.php tests/Unit/MullaBillControllerUnitTest.php --verbose
```

### Run Specific Test Method
```bash
php artisan test --filter test_successful_bill_payment_with_vtpass
```

## Test Database

**Important**: Tests use a separate database to avoid affecting your real data.

### Setup Test Database
1. Copy `.env` to `.env.testing`
2. Update database settings in `.env.testing`:
```
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
# OR use a separate test database
DB_DATABASE=mulla_test
```

## Understanding Test Results

### ✅ Green (Passed)
- Test passed successfully
- Your code works as expected

### ❌ Red (Failed)
- Test failed
- Shows what went wrong and where

### 🟡 Yellow (Skipped)
- Test was skipped (rare)

## Test Structure Explanation

### setUp() Method
```php
protected function setUp(): void
{
    parent::setUp();
    // This runs before EACH test
    // Creates fresh test data for every test
}
```

### Assertions (The "Checks")
```php
$response->assertStatus(200);        // HTTP status code is 200
$response->assertJson(['key' => 'value']); // Response contains this JSON
$this->assertEquals($expected, $actual);    // Two values are equal
$this->assertTrue($condition);              // Condition is true
$this->assertDatabaseHas('table', ['id' => 1]); // Database contains this record
```

### HTTP Mocking
```php
Http::fake([
    '*vtpass.com*' => Http::response(['data' => 'fake'], 200)
]);
// This prevents real API calls during testing
// Returns fake responses instead
```

## Common Test Scenarios Covered

### 1. Security Tests
- ✅ Negative amounts rejected
- ✅ Insufficient balance rejected  
- ✅ Duplicate payment references rejected
- ✅ Unauthenticated access blocked

### 2. Business Logic Tests
- ✅ VTPass payment success
- ✅ SafeHaven fallback when VTPass fails
- ✅ Full refund when both services fail
- ✅ Cashback calculation
- ✅ Wallet balance updates

### 3. Integration Tests
- ✅ Database transactions
- ✅ External API calls (mocked)
- ✅ Email notifications (mocked)
- ✅ Rate limiting

## Adding New Tests

### For New Endpoint
```php
public function test_new_endpoint_does_something()
{
    // 1. Arrange (set up test data)
    Sanctum::actingAs($this->user);
    
    // 2. Act (perform the action)
    $response = $this->postJson('/api/endpoint', ['data' => 'value']);
    
    // 3. Assert (check results)
    $response->assertStatus(200);
    $this->assertDatabaseHas('table', ['field' => 'value']);
}
```

### For New Validation Rule
```php
public function test_validation_rejects_invalid_data()
{
    Sanctum::actingAs($this->user);
    
    $response = $this->postJson('/api/endpoint', [
        'required_field' => null // Invalid data
    ]);
    
    $response->assertStatus(422); // Validation error
}
```

## Test Data Factories

Laravel Factories create fake test data:

```php
$user = User::factory()->create(['email' => 'test@example.com']);
// Creates a user with fake data + your specific email
```

## Debugging Failed Tests

### 1. Read the Error Message
```
FAILED tests/Feature/MullaBillControllerTest.php::test_payment_success
AssertionFailedError: Expected status code 200 but received 422.
```

### 2. Add Debug Output
```php
public function test_something()
{
    $response = $this->postJson('/api/endpoint', $data);
    
    // Debug the response
    dump($response->json());        // See response data
    dump($response->getContent());  // See raw response
    
    $response->assertStatus(200);
}
```

### 3. Check Logs
```bash
tail -f storage/logs/laravel.log
```

## Best Practices

### ✅ Do
- Test happy path (success cases)
- Test error cases (failures)
- Test edge cases (boundary conditions)
- Use descriptive test names
- Keep tests independent

### ❌ Don't
- Make real API calls in tests
- Depend on external services
- Test multiple things in one test
- Use production data in tests

## Environment Variables for Testing

Add to `.env.testing`:
```
VTPASS_API_KEY=fake_key_for_testing
SAFE_HAVEN_CLIENT_ID=fake_client_id
SAFE_HAVEN_CLIENT_ASSERTION=fake_assertion

# Use fake/test values for external services
```

## Running Tests in CI/CD

Tests should run automatically when you:
1. Push code to repository
2. Create pull requests  
3. Deploy to staging

Example GitHub Actions workflow:
```yaml
- name: Run Tests
  run: php artisan test --coverage
```

This ensures your code always works before deployment!