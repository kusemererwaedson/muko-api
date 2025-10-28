<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\CommunicationController;
use App\Http\Controllers\Api\AcademicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| Routes are grouped by functionality and protected routes use Sanctum.
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Handle unauthenticated requests
Route::get('/login', fn() => response()->json(['message' => 'Unauthenticated'], 401))
    ->name('login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Students
    Route::prefix('students')->group(function () {
        Route::get('/export', [StudentController::class, 'export']);
        Route::post('/import', [StudentController::class, 'import']);
        Route::post('/bulk', [StudentController::class, 'bulkStore']);
        Route::get('/template', [StudentController::class, 'template']);
        Route::get('/classes', [StudentController::class, 'getClasses']);
        Route::get('/streams/{classId?}', [StudentController::class, 'getStreams']);

    });
    Route::apiResource('students', StudentController::class);

    // Fees
    Route::prefix('fees')->group(function () {
        Route::get('/dashboard', [FeeController::class, 'dashboard']);
        Route::post('/send-reminders', [FeeController::class, 'sendReminders']);
        Route::get('/reports', [FeeController::class, 'reports']);

        // Fee Types
        Route::get('/types', [FeeController::class, 'types']);
        Route::post('/types', [FeeController::class, 'storeType']);

        // Fee Groups
        Route::get('/groups', [FeeController::class, 'groups']);
        Route::post('/groups', [FeeController::class, 'storeGroup']);

        // Fee Allocations
        Route::get('/allocations', [FeeController::class, 'allocations']);
        Route::post('/allocations', [FeeController::class, 'storeAllocation']);

        // Fee Payments
        Route::get('/payments', [FeeController::class, 'payments']);
        Route::post('/payments', [FeeController::class, 'storePayment']);
    });

    // Accounting
    Route::prefix('accounting')->group(function () {
        Route::get('/accounts', [AccountingController::class, 'accounts']);
        Route::post('/accounts', [AccountingController::class, 'storeAccount']);
        Route::get('/voucher-heads', [AccountingController::class, 'voucherHeads']);
        Route::post('/voucher-heads', [AccountingController::class, 'storeVoucherHead']);
        Route::get('/transactions', [AccountingController::class, 'transactions']);
        Route::post('/transactions', [AccountingController::class, 'storeTransaction']);
    });

    // Academic Management
    Route::prefix('academic')->group(function () {
        Route::get('/levels', [AcademicController::class, 'levels']);
        Route::post('/levels', [AcademicController::class, 'storeLevel']);
        Route::get('/classes', [AcademicController::class, 'classes']);
        Route::post('/classes', [AcademicController::class, 'storeClass']);
        Route::get('/streams', [AcademicController::class, 'streams']);
        Route::post('/streams', [AcademicController::class, 'storeStream']);
        Route::post('/classes/{classId}/streams', [AcademicController::class, 'attachStream']);
        Route::delete('/classes/{classId}/streams/{streamId}', [AcademicController::class, 'detachStream']);
        Route::get('/terms', [AcademicController::class, 'terms']);
        Route::post('/terms', [AcademicController::class, 'storeTerm']);
        Route::patch('/terms/{id}/set-current', [AcademicController::class, 'setCurrentTerm']);
    });

    // Communications
    Route::prefix('communications')->group(function () {
        Route::get('/email-logs', [CommunicationController::class, 'emailLogs']);
        Route::post('/bulk-reminders', [CommunicationController::class, 'bulkReminders']);
        Route::get('/sms-logs', [CommunicationController::class, 'smsLogs']);
        Route::post('/send-sms', [CommunicationController::class, 'sendSms']);
    });
});
