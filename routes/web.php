<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RamseyController;
use App\Http\Controllers\UserAccountController;
use App\Http\Controllers\VisitHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MicrosoftController::class, 'showLoginForm'])->name('login');

Route::post('/users', [UserAccountController::class, 'store']);
Route::post('/ramsey/ask', [RamseyController::class, 'ask']);
Route::post('/ramsey/remind', [RamseyController::class, 'remind']);

Route::get('/auth/microsoft', [MicrosoftController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [MicrosoftController::class, 'handleMicrosoftCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/visit-history', [VisitHistoryController::class, 'index'])->name('visit-history');

    Route::resource('appointments', AppointmentController::class);

    Route::middleware('staff')->group(function () {
        Route::post('/dashboard/next-patient', [DashboardController::class, 'nextPatient'])->name('dashboard.next-patient');
        Route::post('/dashboard/treatment-mode', [DashboardController::class, 'toggleTreatmentMode'])->name('dashboard.treatment-mode');
        Route::post('/dashboard/notify-logistics', [DashboardController::class, 'notifyLogistics'])->name('dashboard.notify-logistics');

        Route::post('/doctor-schedules', [AppointmentController::class, 'storeSchedule']);

        Route::resource('inventory', InventoryController::class)->only([
            'index', 'store', 'update',
        ]);

        Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records');
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);
        Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);

        Route::get('/records', [MedicalRecordController::class, 'index']);
        Route::post('/records', [MedicalRecordController::class, 'store']);
        Route::delete('/records/{id}', [MedicalRecordController::class, 'destroy']);

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    });
});
