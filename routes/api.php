<?php

use App\Http\Controllers\Api\ExternalReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function () {
    Route::post('v1/external/receipts', [ExternalReceiptController::class, 'store'])
        ->name('api.external.receipts.store');
});
