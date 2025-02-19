<?php

namespace App\Services;

use App\Models\UserAltBankAccountsModel;
use App\Traits\Reusables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class ComplianceService
{
    use Reusables;

    private $bankMappings = [
        '999992' => 'Opay',
        '999991' => 'Palmpay',
        '120003' => 'MTN Momo',
        '50515' => 'Moniepoint',
        '51318' => 'Fairmoney',
    ];

    public function resolveAccount(array $data)
    {        
        if (!isset($data['account_number']) || !isset($data['first_name']) || !isset($data['last_name'])) {
            return response(['message' => 'An error occurred, check the data you entered.'], 400);
        }

        $accountNumber = $data['account_number'];
        $firstName = Str::lower($data['first_name']);
        $lastName = Str::lower($data['last_name']);
        $processedAccount = ltrim($accountNumber, '0');

        $bankResponses = [];
        // Initialize with bank names as keys
        $namesFromBanks = array_fill_keys(array_values($this->bankMappings), false);
        $failedAttempts = 0;

        foreach ($this->bankMappings as $bankCode => $bankName) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('MULLA_PAYSTACK_LIVE'),
                ])->get('https://api.paystack.co/bank/resolve', [
                    'account_number' => $processedAccount,
                    'bank_code' => $bankCode
                ]);

                $responseData = $response->json();

                if ($response->successful() && $responseData['status'] && isset($responseData['data']['account_name'])) {
                    $accountName = $responseData['data']['account_name'];
                    $namesFromBanks[$bankName] = $accountName;
                    $bankResponses[] = [
                        'bank' => $bankName,
                        'name' => $accountName,
                        'parsed' => $this->parseAccountName($accountName),
                    ];
                } else {
                    $failedAttempts++;
                }
            } catch (\Exception $e) {
                $failedAttempts++;
            }
        }

        $matchPercentage = $this->calculateMatchPercentage($bankResponses, $firstName, $lastName);
        $allComponents = array_merge(...array_column($bankResponses, 'parsed'));

        $this->sendToDiscord(
            $this->formatDiscordMessage(
                $data['first_name'],
                $data['last_name'],
                $matchPercentage,
                $namesFromBanks,
                $allComponents
            )
        );

        UserAltBankAccountsModel::where('user_id', $data['user_id'])->update([
            'alt_bank_data' => json_encode([
                'original_name' => $data['first_name'] . ' ' . $data['last_name'],
                'names_from_banks' => $namesFromBanks,
                'match_percentage' => $matchPercentage . '%',
            ])
        ]);

        return;

        // return response()->json([
        //     'original_name' => $data['first_name'] . ' ' . $data['last_name'],
        //     'names_from_banks' => $namesFromBanks,
        //     'match_percentage' => $matchPercentage . '%',
        // ]);
    }

    private function parseAccountName(string $name): array
    {
        $parts = explode(',', $name, 2);
        $combined = trim($parts[0]) . (isset($parts[1]) ? ' ' . trim($parts[1]) : '');
        return array_map('strtolower', preg_split('/\s+/', $combined, -1, PREG_SPLIT_NO_EMPTY));
    }

    private function calculateMatchPercentage(array $responses, string $firstName, string $lastName): int
    {
        if (empty($responses)) return 0;

        if (count($responses) === 1) {
            $components = $responses[0]['parsed'];
            return match (true) {
                in_array($firstName, $components) && in_array($lastName, $components) => 100,
                in_array($firstName, $components) || in_array($lastName, $components) => 50,
                default => 0
            };
        }

        $allComponents = array_merge(...array_column($responses, 'parsed'));
        return match (true) {
            in_array($firstName, $allComponents) && in_array($lastName, $allComponents) => 100,
            in_array($firstName, $allComponents) || in_array($lastName, $allComponents) => 50,
            default => 0
        };
    }

    private function formatDiscordMessage(
        string $originalFirstName,
        string $originalLastName,
        int $matchPercentage,
        array $namesFromBanks,
        array $allComponents
    ): string {
        $message = "**Account Verification Results**\n```diff\n";
        $message .= "! Original Name: $originalFirstName $originalLastName\n";
        $message .= "! Match Confidence: $matchPercentage% " . ($matchPercentage >= 80 ? "✅" : "⚠️") . "\n\n";
        $message .= "=== Bank Verification Details ===\n";

        foreach (array_values($this->bankMappings) as $bankName) {
            $result = $namesFromBanks[$bankName];
            $status = $result ? "✓ " . str_pad($bankName, 12) . ": $result" : "✗ " . str_pad($bankName, 12) . ": No match found";
            $message .= "$status\n";
        }

        $firstNameFound = in_array(Str::lower($originalFirstName), $allComponents) ? '✅' : '❌';
        $lastNameFound = in_array(Str::lower($originalLastName), $allComponents) ? '✅' : '❌';

        $message .= "\n📊 Match Breakdown:\n";
        $message .= "- $originalFirstName $firstNameFound\n";
        $message .= "- $originalLastName $lastNameFound\n";
        $message .= "```";

        return $message;
    }
}
