<?php

use App\Http\Controllers\Webhooks;
use App\Mail\MullaPasswordResetEmail;
use App\Mail\MullaResetTokenEmail;
use App\Mail\MullaUserInactiveForThirtyDays;
use App\Mail\MullaWelcomeEmail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Hello, World!'
    ]);
});

// Route::get('/mail', function () {
//     return new MullaUserInactiveForThirtyDays([
//         'firstname' => 'Dom',
//     ]);
// });