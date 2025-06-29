<?php

namespace Database\Seeders;

use App\Models\Business\MullaBusinessAccountsModel;
use App\Models\Business\MullaBusinessBulkTransferListModel;
use App\Models\Business\MullaBusinessBulkTransferListItemsModel;
use App\Models\Business\MullaBusinessBulkTransfersModel;
use App\Models\Business\MullaBusinessBulkTransferTransactions;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BusinessTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = MullaBusinessAccountsModel::all();
        $listCount = 0;
        $transferCount = 0;
        $transactionCount = 0;

        foreach ($businesses as $business) {
            // Create 2-3 transfer lists per business
            $listsToCreate = rand(2, 3);
            
            for ($i = 0; $i < $listsToCreate; $i++) {
                $list = $this->createTransferList($business, $i);
                $listCount++;
                
                // Create 5-15 recipients per list
                $this->createListItems($list, rand(5, 15));
                
                // Create 1-2 bulk transfers per business
                if ($i < 2) {
                    $transfer = $this->createBulkTransfer($business, $list, $i);
                    $transferCount++;
                    
                    // Create transactions for the transfer
                    $transactionCount += $this->createBulkTransferTransactions($transfer, $list);
                }
            }
        }

        $this->command->info("✅ Created {$listCount} transfer lists, {$transferCount} bulk transfers, {$transactionCount} transfer transactions");
    }

    private function createTransferList($business, $index): MullaBusinessBulkTransferListModel
    {
        $listTypes = ['employee_salaries', 'vendor_payments', 'contractor_fees', 'supplier_payments'];
        $listTitles = [
            'Monthly Employee Salaries',
            'Vendor Payment List',
            'Contractor Fees Q1',
            'Supplier Payments - December',
            'Bonus Payments',
            'Commission Payments'
        ];

        $id = substr(md5(uniqid(rand(), true)), 0, 12);
        
        return MullaBusinessBulkTransferListModel::create([
            'id' => $id,
            'business_id' => $business->id,
            'title' => $listTitles[array_rand($listTitles)],
            'type' => $listTypes[array_rand($listTypes)],
        ]);
    }

    private function createListItems($list, $itemCount): void
    {
        $banks = [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '058', 'name' => 'Guaranty Trust Bank'],
            ['code' => '057', 'name' => 'Zenith Bank'],
            ['code' => '011', 'name' => 'First Bank of Nigeria'],
            ['code' => '033', 'name' => 'United Bank For Africa'],
            ['code' => '070', 'name' => 'Fidelity Bank'],
            ['code' => '214', 'name' => 'First City Monument Bank'],
            ['code' => '221', 'name' => 'Stanbic IBTC Bank'],
            ['code' => '035', 'name' => 'Wema Bank'],
            ['code' => '032', 'name' => 'Union Bank of Nigeria'],
        ];

        $nigerianNames = [
            'Adebayo Ogundimu', 'Chioma Nwankwo', 'Musa Ibrahim', 'Blessing Okoro',
            'Yakubu Suleiman', 'Funmi Adebola', 'Emeka Eze', 'Halima Abdullahi',
            'Segun Afolabi', 'Ngozi Okafor', 'Bello Ahmad', 'Sola Olaniyi',
            'Khadijah Yusuf', 'Tunde Bakare', 'Amina Garba', 'Chidi Okonkwo',
            'Fatima Hassan', 'Biodun Alabi', 'Usman Aliyu', 'Folake Adeyemi'
        ];

        for ($i = 0; $i < $itemCount; $i++) {
            $bank = $banks[array_rand($banks)];
            $accountNumber = '30' . str_pad(rand(10000000, 99999999), 8, '0');
            $name = $nigerianNames[array_rand($nigerianNames)];
            $amount = $this->generateTransferAmount($list->type);

            MullaBusinessBulkTransferListItemsModel::create([
                'list_id' => $list->id,
                'amount' => $amount * 100, // Store in kobo
                'email' => strtolower(str_replace(' ', '.', $name)) . '@gmail.com',
                'account_name' => strtoupper($name),
                'account_number' => $accountNumber,
                'bank_name' => $bank['name'],
                'bank_code' => $bank['code'],
                'recipient_code' => 'RCP_' . strtoupper(uniqid()),
            ]);
        }
    }

    private function createBulkTransfer($business, $list, $index): MullaBusinessBulkTransfersModel
    {
        // Boolean status: true for completed, false for pending/failed
        $status = rand(1, 100) <= 70; // 70% success rate

        $transferDate = Carbon::now()->subDays(rand(1, 30));

        return MullaBusinessBulkTransfersModel::create([
            'business_id' => $business->id,
            'reference' => 'BULK_' . strtoupper(uniqid()),
            'currency' => 'NGN',
            'reason' => $this->getTransferReason($list->type),
            'status' => $status,
            'created_at' => $transferDate,
            'updated_at' => $transferDate,
        ]);
    }

    private function createBulkTransferTransactions($bulkTransfer, $list): int
    {
        $listItems = MullaBusinessBulkTransferListItemsModel::where('list_id', $list->id)->get();
        $transactionCount = 0;

        foreach ($listItems as $item) {
            // Not all list items are always processed
            if (rand(1, 100) <= 85) { // 85% of items are processed
                $status = $bulkTransfer->status; // Boolean status
                
                // Some individual transactions might fail even in completed bulk transfers
                if ($status && rand(1, 100) <= 5) { // 5% failure rate for successful bulk transfers
                    $status = false;
                }

                $transactionDate = Carbon::parse($bulkTransfer->getRawOriginal('created_at'))->addMinutes(rand(1, 60));
                
                MullaBusinessBulkTransferTransactions::create([
                    'bulk_transfer_id' => $bulkTransfer->id,
                    'reference' => 'TXN_' . strtoupper(uniqid()),
                    'pt_recipient_id' => $item->recipient_code ?? '',
                    'currency' => 'NGN',
                    'amount' => $item->amount, // Already in kobo
                    'recipient_account_no' => $item->account_number,
                    'recipient_account_name' => $item->account_name,
                    'recipient_bank' => $item->bank_name,
                    'status' => $status,
                    'created_at' => $transactionDate,
                ]);
                
                $transactionCount++;
            }
        }

        return $transactionCount;
    }

    private function generateTransferAmount($type): int
    {
        switch ($type) {
            case 'employee_salaries':
                return rand(50000, 500000); // ₦50k - ₦500k salary range
            case 'vendor_payments':
                return rand(100000, 2000000); // ₦100k - ₦2M vendor payments
            case 'contractor_fees':
                return rand(200000, 1000000); // ₦200k - ₦1M contractor fees
            case 'supplier_payments':
                return rand(500000, 5000000); // ₦500k - ₦5M supplier payments
            default:
                return rand(10000, 100000); // Default range
        }
    }

    private function getTransferReason($type): string
    {
        $reasons = [
            'employee_salaries' => 'Monthly salary payment',
            'vendor_payments' => 'Vendor service payment',
            'contractor_fees' => 'Contractor project payment', 
            'supplier_payments' => 'Supplier invoice payment',
        ];

        return $reasons[$type] ?? 'Business payment';
    }
}