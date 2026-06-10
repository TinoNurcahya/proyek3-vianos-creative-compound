<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Seven Caffee Staff Mobile
|--------------------------------------------------------------------------
| Semua endpoint untuk aplikasi mobile staff.
| Base URL: /api
|
| Public  : POST /api/auth/login, POST /api/auth/forgot-password, POST /api/auth/reset-password
| Protected: Semua route lain memerlukan Bearer token (Sanctum)
*/

// ── PUBLIC (tidak butuh token) ────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ── PROTECTED (butuh token Sanctum) ──────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // 1. Auth (token management)
    Route::post('/auth/logout',        [AuthController::class, 'logout']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);

    // 2. Profile
    Route::get('/profile',                     [ProfileController::class, 'show']);
    Route::put('/profile/update',              [ProfileController::class, 'update']);
    Route::put('/profile/change-password',     [ProfileController::class, 'changePassword']);

    // 3. Attendance
    Route::post('/staff/clock-in',             [AttendanceController::class, 'clockIn']);
    Route::post('/staff/clock-out',            [AttendanceController::class, 'clockOut']);
    Route::get('/staff/attendance/summary',    [AttendanceController::class, 'summary']);
    Route::get('/staff/attendance/{date}',     [AttendanceController::class, 'byDate']);
    Route::get('/staff/attendance',            [AttendanceController::class, 'history']);

    // 4. Tables
    Route::get('/staff/tables/layout',         [TableController::class, 'layout']);   
    Route::get('/staff/tables',                [TableController::class, 'index']);
    Route::get('/staff/tables/{id}',           [TableController::class, 'show']);
    Route::put('/staff/tables/{id}',           [TableController::class, 'update']);
    Route::post('/staff/scan-table',           [TableController::class, 'scanQr']);

    // 5. Orders
    Route::get('/staff/orders/status/{status}', [OrderController::class, 'byStatus']);   
    Route::get('/staff/orders/table/{tableId}', [OrderController::class, 'byTable']);    
    Route::get('/staff/orders',                 [OrderController::class, 'index']);
    Route::get('/staff/orders/{id}',            [OrderController::class, 'show']);
    Route::put('/staff/orders/{id}',            [OrderController::class, 'updateStatus']);
    Route::post('/staff/orders/{id}/ready',     [OrderController::class, 'markReady']);
    Route::post('/staff/orders/{id}/served',    [OrderController::class, 'markServed']);

    // 6. Notifications
    Route::post('/notifications/register-device', [NotificationController::class, 'registerDevice']); 
    Route::get('/notifications/unread',            [NotificationController::class, 'unread']);         
    Route::get('/notifications',                   [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read',         [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}',           [NotificationController::class, 'destroy']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']); 
});
