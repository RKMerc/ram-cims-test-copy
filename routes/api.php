<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::prefix('v1')->group(function () {
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::put('/inventory/{code}', [InventoryController::class, 'update']);
});