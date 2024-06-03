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

}
