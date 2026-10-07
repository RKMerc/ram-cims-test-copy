<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\UserAccountController;

Route::prefix('v1')->group(function () {
    Route::post('/users', [UserAccountController::class, 'store']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::put('/inventory/{code}', [InventoryController::class, 'update']);
});