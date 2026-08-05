<?php

use App\Domains\Auth\Controllers\AuthController;
use App\Domains\Auth\Controllers\DebugController;
use App\Domains\Ledger\Controllers\LedgerController;
use App\Domains\Settlement\Controllers\SettlementWebhookController;
use App\Domains\Settlement\Controllers\SettlementDebugController;
use App\Domains\Swap\Controllers\SwapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/webhook/settlement', [SettlementWebhookController::class, 'handle']);

if (config('app.debug')) {
    Route::get('/debug/settlement-signature', [SettlementDebugController::class, 'generateSignature']);
}

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/ledger/{wallet}', [LedgerController::class, 'show']);
    Route::post('/2fa/challenge', [AuthController::class, 'challenge']);
    Route::post('/swap', [SwapController::class, 'swap']);

    if (config('app.debug')) {
        Route::get('/debug/totp', [DebugController::class, 'getTotpCode']);
    }
});
