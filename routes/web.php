<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\MicrosoftController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('inventory', InventoryController::class)->only([
    'index', 'store', 'update'
]);

Route::post('/doctor-schedules', [AppointmentController::class, 'storeSchedule']);
Route::resource('appointments', AppointmentController::class);
Route::get('/medical-records', [MedicalRecordController::class, 'index']);
Route::post('/medical-records', [MedicalRecordController::class, 'store']);
Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);
// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
// });
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/login', [MicrosoftController::class, 'showLoginForm'])->name('login');
Route::get('/auth/microsoft', [MicrosoftController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'handleMicrosoftCallback']);