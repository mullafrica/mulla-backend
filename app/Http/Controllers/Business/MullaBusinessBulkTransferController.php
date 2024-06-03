<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business\MullaBusinessBulkTransfersModel;
use App\Models\Business\MullaBusinessBulkTransferTransactions;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MullaBusinessBulkTransferController extends Controller
{
    use Reusables, UniqueId;

    public function createBulkTransfer(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:mulla_business_accounts_models,id',
            'reason' => 'required',
        ]);

        $reference = $this->uuid12();

        $request->merge(['reference' => $reference]);

        $bulkTransfer = MullaBusinessBulkTransfersModel::create(
            $request->all()
        );

        return response()->json([
            'message' => 'Bulk transfer created successfully',
            'bulk_transfer' => $bulkTransfer,
        ]);
    }

    public function getBulkTransfers()
    {
        $bulkTransfers = MullaBusinessBulkTransfersModel::where('business_id', Auth::user()->id)->with('transactions')->get();
        return response($bulkTransfers, 200);
    }

    public function createBTTransaction(Request $request)
    {
        $request->validate([
            'bulk_transfer_id' => 'required|exists:mulla_business_bulk_transfers_models,id',
            'reference' => 'required',
            'pt_recipient_id' => 'required',
            'currency' => 'required',
            'amount' => 'required|numeric',
            'recipient_account_no' => 'required',
            'recipient_account_name' => 'required',
            'recipient_bank' => 'required',
        ]);

        $transaction = MullaBusinessBulkTransferTransactions::create($request->all());

        return response()->json([
            'message' => 'Transaction created successfully',
            'transaction' => $transaction,
        ]);
    }

    public function getBTBusinessTransactions($id)
    {
        $bulkTransfers = MullaBusinessBulkTransferTransactions::where('bulk_transfer_id', $id)->get();
        return response($bulkTransfers, 200);
    }

    public function uploadTransfers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'id' => 'required|numeric'
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 1000, ',');

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $transferData = array_combine($header, $data);

                MullaBusinessBulkTransferTransactions::updateOrCreate([
                    'reference' => $transferData['REFERENCE'],
                    'bulk_transfer_id' => $request->id,
                ], [
                    'pt_recipient_id' => $transferData['RECIPIENT ID'],
                    'currency' => $transferData['CURRENCY'],
                    'amount' => $transferData['AMOUNT'],
                    'recipient_account_no' => $transferData['RECIPIENT A/C NO'],
                    'recipient_account_name' => $transferData['RECIPIENT A/C NAME'],
                    'recipient_bank' => $transferData['RECIPIENT BANK'],
                    'created_at' => $transferData['TRANSFER DATE'],
                ]);
            }

            fclose($handle);
        }

        return response()->json(['message' => 'CSV data imported successfully'], 200);
    }
}
