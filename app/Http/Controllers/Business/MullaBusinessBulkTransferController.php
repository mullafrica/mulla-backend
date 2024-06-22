<?php

/***
 * 
 * 
 * 
 * 
 * The faster it fails, the better. 
 * 
 * 
 * 
 */

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business\MullaBusinessBulkTransferAlpha;
use App\Models\Business\MullaBusinessBulkTransferListModel;
use App\Models\Business\MullaBusinessBulkTransfersModel;
use App\Models\Business\MullaBusinessBulkTransferTransactions;
use App\Models\Business\MullaBusinessBulkTransferTransactionsAlpha;
use App\Services\BulkTransferService;
use App\Traits\Reusables;
use App\Traits\UniqueId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    public static function getBanks()
    {
        return Cache::remember('pt_banks', 60 * 24 * 7, function () {
            $data = Http::withToken(env('MULLA_PAYSTACK_LIVE'))->get('https://api.paystack.co/bank');
            $banks = $data->object()->data;

            // Extract only the required fields
            $filteredBanks = array_map(function ($bank) {
                return [
                    'id' => $bank->id,
                    'name' => $bank->name,
                    'slug' => $bank->slug,
                    'code' => $bank->code
                ];
            }, $banks);

            return $filteredBanks;
        });
    }

    /**
     * 
     * Lists
     * 
     */
    public function createBulkTransferList(Request $request, BulkTransferService $bts)
    {
        $request->validate([
            'data' => 'required|array',
            'name' => 'required|string'
        ]);

        return $bts->createList(
            $request->data,
            $request->name
        );
    }

    public function getBulkTransferLists(BulkTransferService $bts)
    {
        return $bts->getLists();
    }

    public function getBulkTransferListItems($id, BulkTransferService $bts)
    {
        return $bts->getListItems($id);
    }

    public function deleteBulkTransferList($id, BulkTransferService $bts)
    {
        return $bts->deleteList($id);
    }

    public function initiateBulkTransferAlpha($listId, BulkTransferService $bts) {
        $list = MullaBusinessBulkTransferListModel::find($listId);

        if (!$list) {
            return response()->json(['message' => 'List not found'], 404);
        }

        $status = $bts->initiateTransfer($listId);

        if ($status) {
            return response()->json(['message' => 'Transfer initiated successfully.'], 200);
        } else {
            return response()->json(['message' => 'Transfer failed, check data and try again.'], 500);
        }
    }

    public function getBulkTransferAlpha() {
        return MullaBusinessBulkTransferAlpha::where('business_id', Auth::id())->get();
    }

    public function getBulkTransferTransactions($id) {
        $txns = MullaBusinessBulkTransferTransactionsAlpha::where('transfer_id', $id)->get();

        if (!$txns) {
            return response()->json(['message' => 'Transfers not found.'], 404);
        }

        return $txns;
    }
}
