<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacationRequestController;
use App\Http\Controllers\VacationApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;

// Public routes
Route::post('/auth/dev-login', [AuthController::class, 'devLogin'])->name('auth.dev-login');
Route::post('/auth/superadmin-login', [AuthController::class, 'superadminLogin'])->name('auth.superadmin-login');
Route::get('/auth/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.redirect');
Route::get('/auth/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.callback');

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users
    Route::apiResource('users', UserController::class);
    Route::get('users/{user}/subordinates', [UserController::class, 'subordinates']);
    Route::get('organization/tree', [UserController::class, 'organizationTree']);

    // Vacation Requests (calendar antes do resource para não capturar "calendar" como id)
    Route::get('vacation-requests/calendar', [VacationRequestController::class, 'calendar']);
    Route::apiResource('vacation-requests', VacationRequestController::class);

    // Vacation Approvals
    Route::post('vacation-requests/{vacation}/approve', [VacationApprovalController::class, 'approve']);
    Route::post('vacation-requests/{vacation}/reject', [VacationApprovalController::class, 'reject']);
    Route::get('approvals/pending', [VacationApprovalController::class, 'pending']);

    // Reports
    Route::get('reports/vacations', [ReportController::class, 'vacations']);
    Route::get('reports/export-pdf', [ReportController::class, 'exportPdf']);
    Route::get('reports/export-excel', [ReportController::class, 'exportExcel']);
    Route::get('reports/audit-logs', [ReportController::class, 'auditLogs']);

    // Roles (Admin only)
    Route::apiResource('roles', RoleController::class);

    // Settings (Admin only)
    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/{key}', [SettingController::class, 'show']);
    Route::put('settings/{key}', [SettingController::class, 'update'])->middleware('can:admin,App\Models\User');
});

// Frontend fallback (SPA)
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint not found'], 404);
});
