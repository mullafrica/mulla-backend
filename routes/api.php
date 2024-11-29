<?php

use App\Http\Controllers\Business\MullaBusinessAuthController;
use App\Http\Controllers\Business\MullaBusinessBulkTransferController;
use App\Http\Controllers\MullaAuthController;
use App\Http\Controllers\MullaBillController;
use App\Http\Controllers\MullaPersonalAdminController;
use App\Http\Controllers\MullaPushNotificationController;
use App\Http\Controllers\MullaStatsController;
use App\Http\Controllers\MullaTransactionsController;
use App\Http\Controllers\MullaTransferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\Webhooks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
// Route::post('/comet/auth/verify', function (Request $request) {
//     return response(['message' => 'New account registration temporarily suspended, please check back later.'], 400);
// });


Route::post('/comet/auth/token', [MullaAuthController::class, 'sendToken']);
Route::post('/comet/auth/password/reset', [MullaAuthController::class, 'resetPassword']);
Route::post('/comet/bloc/webhook', [Webhooks::class, 'blocWebhooks']);
Route::post('/comet/webhook/all', [Webhooks::class, 'all']);

Route::post('/business/auth/register', [MullaBusinessAuthController::class, 'register']);
Route::post('/business/auth/login', [MullaBusinessAuthController::class, 'login']);

Route::get('/m/banks', function () {
    return MullaBusinessBulkTransferController::getBanks();
});

Route::get('/admin/users', [MullaPersonalAdminController::class, 'getAllUsers']);
Route::get('/admin/stats', [MullaPersonalAdminController::class, 'getAllStats']);
Route::get('/admin/transactions', [MullaPersonalAdminController::class, 'getAllTransactions']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/comet/logout', [MullaAuthController::class, 'logout']);
    // Route::get('/comet/users/all', [MullaAuthController::class, 'all']);
    Route::get('/comet/user', [MullaAuthController::class, 'getUser']);

    Route::get('/comet/verify/token', function (Request $request) {
        return true;
    });

    Route::get('/comet/supported/ops', [MullaBillController::class, 'getVTPassOperatorProducts']);
    Route::get('/comet/supported/ops/variation', [MullaBillController::class, 'getVTPassOperatorProductVariation']);

    Route::get('/comet/meter/validate/{op_id}', [MullaBillController::class, 'validateVTPassMeter']);
    Route::get('/comet/tv/validate/{op_id}', [MullaBillController::class, 'validateSmartCardNumber']);

    Route::get('/comet/user/meters',  [MullaBillController::class, 'getUserMeters']);
    Route::get('/comet/user/tv/card/numbers',  [MullaBillController::class, 'getUserTvCardNumbers']);
    Route::get('/comet/user/airtime/numbers',  [MullaBillController::class, 'getUserAirtimeNumbers']);
    Route::get('/comet/user/internetdata/numbers',  [MullaBillController::class, 'getUserInternetDataNumbers']);
    Route::post('/comet/user/fcm',  [MullaAuthController::class, 'updateFcm']);

    Route::get('/comet/user/wallets',  [MullaAuthController::class, 'getUserWallets']);

    // Store and get txns
    Route::post('/comet/txn/store', [MullaTransactionsController::class, 'storeTxn']);
    Route::get('/comet/user/txn', [MullaTransactionsController::class, 'getUserTxns']);
    Route::get('/comet/user/txn/all', [MullaTransactionsController::class, 'getAllUserTxns']);

    Route::get(
        '/comet/operator/products/{operatorId}/{bill}',
        [MullaBillController::class, 'getOperatorProducts']
    );

    Route::middleware(['throttle:5,1']) // 1 request per minute
        ->group(function () {
            Route::post('/comet/bill/pay', [MullaBillController::class, 'payVTPassBill']);
            Route::post('/comet/wallet/pay', [WalletController::class, 'payWithWallet']);
        });

    // Wallet
    Route::get('/comet/wallet/dva', [WalletController::class, 'getVirtualAccount']);

    // Transfer
    Route::get('/comet/transfer/banks', [MullaTransferController::class, 'getBanks']);
    Route::get('/comet/transfer/beneficiaries', [MullaTransferController::class, 'getBeneficiaries']);
    Route::post('/comet/transfer/beneficiaries', [MullaTransferController::class, 'saveBeneficiaries']);
    Route::post('/comet/transfer/beneficiary/validate', [MullaTransferController::class, 'validateAccount']);
    Route::post('/comet/transfer', [MullaTransferController::class, 'completeTransfer']);

    // Stats
    Route::get('/comet/account/stats', [MullaStatsController::class, 'getStats']);
    Route::get('/comet/account/txn/lastfive', [MullaStatsController::class, 'getLastFiveTxns']);

    Route::post('/comet/push/notification', [MullaPushNotificationController::class, 'sendNotification']);
    Route::post('/comet/push/notification/all', [MullaPushNotificationController::class, 'sendNotificationToAll']);
});


Route::middleware('auth:business')->group(function () {
    Route::post('/business/bulktransfer', [MullaBusinessBulkTransferController::class, 'createBulkTransfer']);
    Route::get('/business/bulktransfer', [MullaBusinessBulkTransferController::class, 'getBulkTransfers']);

    Route::post('/business/bulktransfer/alpha/{listId}', [MullaBusinessBulkTransferController::class, 'initiateBulkTransferAlpha']);
    Route::get('/business/bulktransfer/alpha', [MullaBusinessBulkTransferController::class, 'getBulkTransferAlpha']);
    Route::get('/business/bulktransfer/alpha/transactions/{id}', [MullaBusinessBulkTransferController::class, 'getBulkTransferTransactions']);


    Route::post('/business/bt/transaction', [MullaBusinessBulkTransferController::class, 'createBTTransaction']);
    Route::get('/business/bt/transaction/{id}', [MullaBusinessBulkTransferController::class, 'getBTBusinessTransactions']);

    Route::post('/business/bt/transactions/upload', [MullaBusinessBulkTransferController::class, 'uploadTransfers']);

    Route::post('/business/bulk-transfer/list', [MullaBusinessBulkTransferController::class, 'createBulkTransferList']);
    Route::get('/business/bulk-transfer/list', [MullaBusinessBulkTransferController::class, 'getBulkTransferLists']);
    Route::get('/business/bulk-transfer/list/{id}', [MullaBusinessBulkTransferController::class, 'getBulkTransferListItems']);
    Route::delete('/business/bulk-transfer/list/{id}', [MullaBusinessBulkTransferController::class, 'deleteBulkTransferList']);
});
