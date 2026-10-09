<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DutySlotController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RamseyController;
use App\Http\Controllers\UserAccountController;
use App\Http\Controllers\VisitHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
Route::get('/register', [LoginController::class, 'showRegister'])->name('register.show');
Route::post('/register', [LoginController::class, 'register'])->middleware('throttle:5,1')->name('register');

if (app()->environment('local')) {
    Route::get('/preview/{role}', [PreviewController::class, 'enter'])->name('preview');
}

Route::post('/users', [UserAccountController::class, 'store']);

Route::get('/auth/microsoft', [LoginController::class, 'redirectToMicrosoft'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [LoginController::class, 'handleMicrosoftCallback']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/portal/view', [LoginController::class, 'switchView'])->name('portal.view');

    Route::post('/ramsey/ask', [RamseyController::class, 'ask'])->name('ramsey.ask');
    Route::post('/ramsey/view', [RamseyController::class, 'view'])->name('ramsey.view');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/users', [UserAccountController::class, 'index'])->name('users.index');
    Route::put('/users/{id}', [UserAccountController::class, 'update']);
    Route::get('/visit-history', [VisitHistoryController::class, 'index'])->name('visit-history');

    Route::get('/appointments/slots', [AppointmentController::class, 'slots'])->name('appointments.slots');
    Route::resource('appointments', AppointmentController::class);
    Route::post('/duty-slots/book', [DutySlotController::class, 'book'])->name('duty-slots.book');
    Route::post('/duty-slots/cancel', [DutySlotController::class, 'cancel'])->name('duty-slots.cancel');
    Route::post('/duty-slots/toggle', [DutySlotController::class, 'toggle'])->name('duty-slots.toggle');
    Route::post('/duty-slots/assign', [DutySlotController::class, 'assign'])->name('duty-slots.assign');

    Route::middleware('staff')->group(function () {
        Route::post('/dashboard/next-patient', [DashboardController::class, 'nextPatient'])->name('dashboard.next-patient');
        Route::post('/dashboard/treatment-mode', [DashboardController::class, 'toggleTreatmentMode'])->name('dashboard.treatment-mode');
        Route::post('/doctor-schedules', [AppointmentController::class, 'storeSchedule']);
        Route::post('/inventory/notify-logistics', [InventoryController::class, 'notifyLogistics'])->name('inventory.notify-logistics');

        Route::resource('inventory', InventoryController::class)->only([
            'index', 'store', 'update', 'destroy',
        ]);

        Route::get('/medical-records', [MedicalRecordController::class, 'index'])->name('medical-records');
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);
        Route::put('/medical-records/{id}', [MedicalRecordController::class, 'update']);
        Route::delete('/medical-records/{id}', [MedicalRecordController::class, 'destroy']);

        Route::get('/records', [MedicalRecordController::class, 'index']);
        Route::post('/records', [MedicalRecordController::class, 'store']);
        Route::put('/records/{id}', [MedicalRecordController::class, 'update']);
        Route::delete('/records/{id}', [MedicalRecordController::class, 'destroy']);

        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/report', [AnalyticsController::class, 'report'])->name('analytics.report');
        Route::get('/analytics/report/download', [AnalyticsController::class, 'download'])->name('analytics.report.download');
    });
});
