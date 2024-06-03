<?php

use App\Http\Controllers\Business\MullaBusinessAuthController;
use App\Http\Controllers\Business\MullaBusinessBulkTransferController;
use App\Http\Controllers\MullaAuthController;
use App\Http\Controllers\MullaBillController;
use App\Http\Controllers\MullaTransactionsController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Webhooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

/////////////////////////////// COMET //////////////////////////////
Route::post('/comet/auth', [MullaAuthController::class, 'login']);
Route::post('/comet/auth/register', [MullaAuthController::class, 'register']);
Route::post('/comet/auth/verify', [MullaAuthController::class, 'registrationToken']);

Route::post('/comet/auth/token', [MullaAuthController::class, 'sendToken']);
Route::post('/comet/auth/password/reset', [MullaAuthController::class, 'resetPassword']);
Route::post('/comet/bloc/webhook', [Webhooks::class, 'blocWebhooks']);
Route::post('/comet/webhook/all', [Webhooks::class, 'all']);

Route::post('/business/auth/register', [MullaBusinessAuthController::class, 'register']);
Route::post('/business/auth/login', [MullaBusinessAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/comet/logout', [MullaAuthController::class, 'logout']);
    // Route::get('/comet/users/all', [MullaAuthController::class, 'all']);
    Route::get('/comet/users/{id}', [MullaAuthController::class, 'getUser']);

    Route::get('/comet/supported/ops', [MullaBillController::class, 'getVTPassOperatorProducts']);
    Route::get('/comet/supported/ops/variation', [MullaBillController::class, 'getVTPassOperatorProductVariation']);

    Route::get('/comet/meter/validate/{op_id}', [MullaBillController::class, 'validateVTPassMeter']);
    Route::get('/comet/tv/validate/{op_id}', [MullaBillController::class, 'validateSmartCardNumber']);

    Route::get('/comet/user/meters',  [MullaBillController::class, 'getUserMeters']);
    Route::get('/comet/user/tv/card/numbers',  [MullaBillController::class, 'getUserTvCardNumbers']);
    Route::get('/comet/user/airtime/numbers',  [MullaBillController::class, 'getUserAirtimeNumbers']);

    Route::get('/comet/user/wallets',  [MullaAuthController::class, 'getUserWallets']);

    // Store and get txns
    Route::post('/comet/txn/store', [MullaTransactionsController::class, 'storeTxn']);
    Route::get('/comet/user/txn', [MullaTransactionsController::class, 'getUserTxns']);
    Route::get('/comet/user/txn/all', [MullaTransactionsController::class, 'getAllUserTxns']);

    Route::get(
        '/comet/operator/products/{operatorId}/{bill}',
        [MullaBillController::class, 'getOperatorProducts']
    );

    Route::post('/comet/bill/pay', [MullaBillController::class, 'payVTPassBill']);

    // Wallet
    Route::get('/comet/wallet/dva', [WalletController::class, 'getVirtualAccount']);
    Route::post('/comet/wallet/pay', [WalletController::class, 'payWithWallet']);
});


Route::middleware('auth:business')->group( function () {
    Route::post('/business/bulktransfer', [MullaBusinessBulkTransferController::class, 'createBulkTransfer']);
    Route::get('/business/bulktransfer', [MullaBusinessBulkTransferController::class, 'getBulkTransfers']);

    Route::post('/business/bt/transaction', [MullaBusinessBulkTransferController::class, 'createBTTransaction']);
    Route::get('/business/bt/transaction/{id}', [MullaBusinessBulkTransferController::class, 'getBTBusinessTransactions']);

    
});