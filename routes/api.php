<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeeController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\CommunicationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Students
    Route::apiResource('students', StudentController::class);
    
    // Fee Types
    Route::get('/fees/types', [FeeController::class, 'types']);
    Route::post('/fees/types', [FeeController::class, 'storeType']);
    
    // Fee Groups
    Route::get('/fees/groups', [FeeController::class, 'groups']);
    Route::post('/fees/groups', [FeeController::class, 'storeGroup']);
    
    // Fee Allocations
    Route::get('/fees/allocations', [FeeController::class, 'allocations']);
    Route::post('/fees/allocations', [FeeController::class, 'storeAllocation']);
    
    // Fee Payments
    Route::get('/fees/payments', [FeeController::class, 'payments']);
    Route::post('/fees/payments', [FeeController::class, 'storePayment']);
    
    // Fee Management
    Route::prefix('fees')->group(function () {
        Route::get('/dashboard', [FeeController::class, 'dashboard']);
        Route::post('/send-reminders', [FeeController::class, 'sendReminders']);
        
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
    
    // Communications
    Route::prefix('communications')->group(function () {
        Route::get('/email-logs', [CommunicationController::class, 'emailLogs']);
        Route::post('/bulk-reminders', [CommunicationController::class, 'bulkReminders']);
    });
});