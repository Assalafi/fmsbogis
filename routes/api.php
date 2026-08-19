<?php

use App\Http\Controllers\Api\ExternalReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function () {
    Route::post('v1/external/receipts', [ExternalReceiptController::class, 'store'])
        ->name('api.external.receipts.store');
    Route::get('v1/external/receipts', [ExternalReceiptController::class, 'index'])
        ->name('api.external.receipts.index');
    Route::get('v1/external/receipts/{reference}/pdf', [ExternalReceiptController::class, 'pdf'])
        ->name('api.external.receipts.pdf');
});
