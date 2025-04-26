<?php

namespace App\Http\Controllers;

use App\Enums\BaseUrls;
use App\Models\CustomerVirtualAccountsModel;
use App\Models\MullaUserTransactions;
use App\Models\MullaUserWallets;
use App\Models\User;
use App\Services\VirtualAccount;
use App\Services\WalletService;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletController extends Controller
{
    use UniqueId;

    public function getVirtualAccount(VirtualAccount $va)
    {
        if ($dva = CustomerVirtualAccountsModel::where('user_id', Auth::id())->get()) {
            return $dva;
        } else {
            $user = Auth::user();

            $pt = $va->createCustomer([
                'user_id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
            ]);

            $dva = $va->createVirtualAccount($pt, [
                'user_id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'phone' => $user->phone,
            ]);

            if ($dva) {
                return response(CustomerVirtualAccountsModel::where('user_id', Auth::id())->get(), 200);
            } else {
                return response('An error occured', 400);
            }
        }
    }

    public function payWithWallet(Request $request, WalletService $ws)
    {
        $request->validate([
            'amount' => 'required|numeric|min:50',
        ]);

        if ($ws->checkBalance($request->amount * BaseUrls::MULTIPLIER)) {

            $m = MullaUserTransactions::create(
                [
                    'user_id' => Auth::id(),
                    'payment_reference' => $this->uuid(),
                    'amount' => $request->amount,
                    'status' => false
                ]
            );

            return response(['reference' => $m->payment_reference], 200);
        } else {
            return response(['message' => 'Your balance is insufficient, please fund your wallet.', 'status' => false], 200);
        }
    }

    public static function createTitanAccountsForAllCustomers(): array
    {
        $secretKey = env('MULLA_PAYSTACK_LIVE');
        $apiUrl = BaseUrls::PAYSTACK;

        if (!$secretKey) {
            Log::error('Paystack Service Error: Secret key is not configured.');
            return [
                'success' => false,
                'message' => 'Paystack secret key is not configured in services config or .env.',
                'created_count' => 0,
                'errors' => ['config' => 'Missing Paystack Secret Key']
            ];
        }

        $createdCount = 0;
        $errors = [];

        try {
            // 1. Get unique customer IDs and their associated user IDs from the model
            // We select distinct customer_id and assume one user_id per customer_id based on context.
            // If a customer_id could map to multiple user_ids, this needs refinement.
            $customersToProcess = CustomerVirtualAccountsModel::select('user_id', 'customer_id')
                ->distinct('customer_id')
                ->get();

            if ($customersToProcess->isEmpty()) {
                Log::info('Paystack Service: No customer records found to process for Titan accounts.');
                return ['success' => true, 'message' => 'No customer records found.', 'created_count' => 0, 'errors' => []];
            }

            Log::info("Paystack Service: Found {$customersToProcess->count()} unique customers to potentially create Titan accounts for.");

            // 2. Iterate through each unique customer
            foreach ($customersToProcess as $customerInfo) {
                $customerId = $customerInfo->customer_id;
                $userId = $customerInfo->user_id;

                // Skip if customer ID is missing (data integrity check)
                if (empty($customerId)) {
                    Log::warning("Paystack Service: Skipping record with missing customer_id for user_id {$userId}.");
                    continue;
                }

                try {
                    // 3. Check if a Titan account *already exists* for this customer_id in our DB
                    $existingTitanAccount = CustomerVirtualAccountsModel::where('customer_id', $customerId)
                        ->where('bank_slug', BaseUrls::TARGET_BANK_SLUG)
                        ->exists(); // More efficient than ->first() if you only need existence check

                    if ($existingTitanAccount) {
                        Log::info("Paystack Service: Titan account already exists for customer {$customerId}. Skipping API call.");
                        continue; // Move to the next customer
                    }

                    // 4. Make the cURL request using Laravel's HTTP Client
                    Log::info("Paystack Service: Requesting Titan account for customer {$customerId}.");
                    $response = Http::withHeader('Authorization', 'Bearer ' .($secretKey))
                        ->post($apiUrl . 'dedicated_account', [
                            'customer' => $customerId,
                            'preferred_bank' => BaseUrls::TARGET_BANK_SLUG
                        ]);

                    // 5. Handle the response
                    if (!$response->successful()) {
                        // Log HTTP-level errors (4xx, 5xx)
                        $errorMessage = $response->body(); // Get error body from Paystack
                        Log::error("Paystack Service: API HTTP error for customer {$customerId}. Status: {$response->status()}. Response: {$errorMessage}");
                        $errors[$customerId] = "API HTTP Error: Status {$response->body()}";
                        continue; // Move to the next customer
                    }

                    $responseData = $response->json();

                    // Check Paystack's logical status in the response
                    if (!isset($responseData['status']) || $responseData['status'] !== true || !isset($responseData['data'])) {
                        $paystackMessage = $responseData['message'] ?? 'Unknown Paystack error response';
                        Log::error("Paystack Service: Failed to create account for customer {$customerId}. Message: {$paystackMessage}");
                        $errors[$customerId] = "Paystack Logic Error: {$paystackMessage}";
                        continue; // Move to the next customer
                    }

                    // 6. Extract data and create new record in DB
                    $accountData = $responseData['data'];
                    $bankData = $accountData['bank'];

                    // Ensure necessary data exists before creating
                    if (!isset($bankData['name'], $bankData['slug'], $accountData['account_name'], $accountData['account_number'])) {
                        Log::error("Paystack Service: Incomplete data received from Paystack for customer {$customerId}.");
                        $errors[$customerId] = "Incomplete data from Paystack.";
                        continue;
                    }

                    // DB::transaction(function () use ($userId, $customerId, $accountData, $bankData) { // Optional transaction
                    CustomerVirtualAccountsModel::create([
                        'user_id'        => $userId,
                        'customer_id'    => $customerId, // Should match the one sent
                        'bank_id'        => $bankData['id'] ?? null, // Make sure your DB column allows null or handle default
                        'bank_name'      => $bankData['name'],
                        'bank_slug'      => $bankData['slug'],
                        'account_name'   => $accountData['account_name'],
                        'account_number' => $accountData['account_number'],
                        // Ensure 'created_at' and 'updated_at' are handled by Eloquent timestamps
                    ]);
                    // }); // End Optional transaction

                    $createdCount++;
                    Log::info("Paystack Service: Successfully created and saved Titan account for customer {$customerId}.");
                } catch (Throwable $e) {
                    // Catch exceptions during processing for a single customer (DB errors, etc.)
                    Log::error("Paystack Service: Error processing customer {$customerId}: " . $e->getMessage());
                    $errors[$customerId] = "System Error: " . $e->getMessage();
                }
            } // End foreach loop

        } catch (Throwable $e) {
            // Catch exceptions during initial data fetch or general setup
            Log::error("Paystack Service: A critical error occurred: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'A critical error occurred during processing.',
                'created_count' => $createdCount,
                'errors' => array_merge($errors, ['critical' => $e->getMessage()])
            ];
        }

        $finalMessage = $createdCount . ' new Titan account(s) created.';
        if (!empty($errors)) {
            $finalMessage .= ' ' . count($errors) . ' customer(s) encountered errors.';
        }

        Log::info("Paystack Service: Processing finished. {$finalMessage}");

        // 7. Return summary
        return [
            'success' => empty($errors), // Consider successful if at least one was attempted without critical failure
            'message' => $finalMessage,
            'created_count' => $createdCount,
            'errors' => $errors, // Provides details on which customers failed and why
        ];
    }
}
