<?php

use App\Http\Controllers\Api\ExternalReceiptController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function () {
    Route::post('v1/external/receipts', [ExternalReceiptController::class, 'store'])
        ->name('api.external.receipts.store');
    Route::get('v1/external/receipts', [ExternalReceiptController::class, 'index'])
        ->name('api.external.receipts.index');
    Route::get('v1/external/receipts/{reference}/pdf', [ExternalReceiptController::class, 'pdf'])
        ->name('api.external.receipts.pdf');
});

/*
 * BOGIS Mobile Verifier app (Android).
 */
Route::prefix('v1/mobile')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.mobile.login');

    Route::middleware('mobile.auth')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('api.mobile.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('api.mobile.logout');
        Route::get('receipts', [ReceiptController::class, 'index'])->name('api.mobile.receipts.index');
        Route::get('receipts/{receiptNo}', [ReceiptController::class, 'show'])->name('api.mobile.receipts.show');
    });
});
