<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OwnerController as AdminOwnerController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\RegistrationDraftController;
use App\Http\Controllers\GoogleOnboardingController;
use App\Http\Controllers\RegistrationSubmissionController;
use App\Http\Controllers\PasswordResetController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    if (session('logged_in') && session('user_id')) {
        return redirect()->route('dashboard.user');
    }
    if (session('is_admin')) {
        return redirect()->route('dashboard.admin');
    }
    return redirect()->route('auth.login');
});

Route::get('/healthz', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'backend',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('healthz');

// Authentication routes (legacy URL compatibility)
Route::get('/login.php', [AuthController::class, 'showLoginForm'])->name('auth.login');
Route::post('/login.php', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login.post');
Route::get('/admin-login.php', [AuthController::class, 'showAdminLoginForm'])->name('auth.admin.login');
Route::post('/admin-login.php', [AuthController::class, 'adminLogin'])->middleware('throttle:login')->name('auth.admin.login.post');
Route::post('/google_auth.php', [GoogleOnboardingController::class, 'googleAuth'])->middleware('throttle:oauth')->name('auth.google.token');
Route::post('/finalize_role.php', [GoogleOnboardingController::class, 'finalizeRole'])->middleware('throttle:oauth')->name('auth.google.finalize-role');
Route::post('/logout.php', [AuthController::class, 'logout'])->name('auth.logout');
Route::post('/admin-logout.php', [AuthController::class, 'adminLogout'])->name('auth.admin.logout');
// Keep GET aliases that redirect to login — prevents 404 on stale bookmarks
Route::get('/logout.php',       fn () => redirect()->route('auth.login'))->name('auth.logout.get');
Route::get('/admin-logout.php', fn () => redirect()->route('auth.admin.login'))->name('auth.admin.logout.get');

// Modern Laravel routes (aliases)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/admin/login', [AuthController::class, 'showAdminLoginForm']);
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// User authenticated routes
Route::middleware(['auth.user'])->group(function () {
    // Dashboard
    Route::get('/user-dashboard.php', [DashboardController::class, 'userDashboard'])->name('dashboard.user');
    Route::get('/dashboard', [DashboardController::class, 'userDashboard'])->name('dashboard');
    
    // Vehicle routes (legacy URL compatibility)
    Route::get('/vehicle-registration-form.php', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/register_vehicle.php', [VehicleController::class, 'store'])->middleware('throttle:mutations')->name('vehicles.store');
    Route::get('/vehicle-list.php', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicle-details.php', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::match(['GET', 'POST'], '/search-vehicle.php', [VehicleController::class, 'search'])->middleware('throttle:search')->name('vehicles.search');
    Route::match(['GET', 'POST'], '/scan-vehicle.php', [VehicleController::class, 'scan'])->middleware('throttle:search')->name('vehicles.scan');
    Route::post('/update_vehicle.php', [VehicleController::class, 'update'])->middleware('throttle:mutations')->name('vehicles.update.post');
    Route::post('/delete_vehicle.php', [VehicleController::class, 'destroy'])->middleware('throttle:mutations')->name('vehicles.destroy.post');
    Route::post('/vehicle_operations.php', [VehicleController::class, 'update'])->middleware('throttle:mutations')->name('vehicles.operations.update');
    Route::delete('/vehicle_operations.php', [VehicleController::class, 'destroy'])->middleware('throttle:mutations')->name('vehicles.destroy');
    Route::post('/update-owner-info.php', [OwnerController::class, 'update'])->middleware('throttle:mutations')->name('owners.update.self');
    Route::post('/save_registration_draft.php', [RegistrationDraftController::class, 'save'])->middleware('throttle:mutations')->name('drafts.save');
    Route::post('/submit_registration.php', [RegistrationSubmissionController::class, 'submit'])->middleware('throttle:mutations')->name('registration.submit');
    Route::post('/submit-vehicle-registration.php', [RegistrationSubmissionController::class, 'submitVehicleForm'])->middleware('throttle:mutations')->name('registration.submit.vehicle-form');
    
    // Modern vehicle routes
    Route::resource('vehicles', VehicleController::class)->except(['create', 'store', 'index', 'show']);
    Route::post('/vehicles/{id}/renew', [VehicleController::class, 'renew'])->middleware('throttle:mutations')->name('vehicles.renew');
    
    // Driver routes (legacy URL compatibility)
    Route::post('/driver_operations.php', [DriverController::class, 'store'])->middleware('throttle:mutations')->name('drivers.store');
    Route::put('/driver_operations.php/{id}', [DriverController::class, 'update'])->middleware('throttle:mutations')->name('drivers.update');
    Route::delete('/driver_operations.php/{id}', [DriverController::class, 'destroy'])->middleware('throttle:mutations')->name('drivers.destroy');
    
    // Notification routes
    Route::get('/get_notifications.php', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/mark_notification_read.php', [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('notifications.read.payload');
    Route::post('/mark_notification_read.php/{id}', [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('notifications.read');
});

// Admin authenticated routes
Route::middleware(['auth.admin'])->group(function () {
    // Admin dashboard
    Route::get('/admin-dashboard.php', [DashboardController::class, 'adminDashboard'])->name('dashboard.admin');
    Route::get('/admin/dashboard', [DashboardController::class, 'adminDashboard']);

    // Owner/User management (legacy URL compatibility)
    Route::get('/owner-list.php',      [AdminOwnerController::class, 'index'])->name('owners.index');
    Route::get('/owner-details.php',   [AdminOwnerController::class, 'show'])->name('owners.show');
    Route::get('/edit-owner.php',      [AdminOwnerController::class, 'edit'])->name('owners.edit');
    Route::post('/admin/owners/{id}',  [AdminOwnerController::class, 'update'])->middleware('throttle:mutations')->name('owners.update');

    // User management
    Route::get('/admin-users.php',     [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/view_user.php',       [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/get_users.php',       [AdminUserController::class, 'index'])->name('api.users.index');
    Route::post('/update_user.php',    [AdminUserController::class, 'update'])->middleware('throttle:mutations')->name('users.update');
    Route::post('/delete_user.php',    [AdminUserController::class, 'destroy'])->middleware('throttle:mutations')->name('users.destroy.post');
    Route::delete('/delete_user.php',  [AdminUserController::class, 'destroy'])->middleware('throttle:mutations')->name('users.destroy');

    // Vehicle admin actions
    Route::post('/manage-vehicle-status.php', [AdminVehicleController::class, 'adminStatusUpdate'])->middleware('throttle:mutations')->name('vehicles.status.admin.update');
    Route::post('/admin/vehicles/bulk-approve', [AdminVehicleController::class, 'bulkApprove'])->middleware('throttle:mutations')->name('vehicles.bulk.approve');

    // Reports
    Route::get('/admin_reports.php',   [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/edit_report.php',     [AdminReportController::class, 'edit'])->name('reports.edit');
    Route::post('/edit_report.php',    [AdminReportController::class, 'update'])->middleware('throttle:mutations')->name('reports.update');
    Route::post('/delete_report.php',  [AdminReportController::class, 'destroy'])->middleware('throttle:mutations')->name('reports.destroy.post');
    Route::delete('/delete_report.php',[AdminReportController::class, 'destroy'])->middleware('throttle:mutations')->name('reports.destroy');

    // Modern admin resource routes
    Route::resource('owners',  AdminOwnerController::class)->except(['index', 'show', 'edit', 'update']);
    Route::resource('users',   AdminUserController::class)->except(['index', 'show', 'update', 'destroy']);
    Route::resource('reports', AdminReportController::class)->except(['index', 'edit', 'update', 'destroy']);
});

// Password reset routes (public — no auth required)
Route::get('/forgot-password.php', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password.php', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password.php',   [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password.php',  [PasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');

// Google OAuth routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->middleware('throttle:oauth')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->middleware('throttle:oauth')->name('google.callback');

// Versioned compatibility API (Phase 1 contract freeze)
Route::prefix('api/v1')->middleware(['web'])->group(function () {
    Route::middleware(['auth.user'])->group(function () {
        Route::match(['GET', 'POST'], '/vehicles/search', [VehicleController::class, 'search'])->middleware('throttle:search')->name('api.v1.vehicles.search');
        Route::match(['GET', 'POST'], '/vehicles/scan', [VehicleController::class, 'scan'])->middleware('throttle:search')->name('api.v1.vehicles.scan');
        Route::post('/drivers', [DriverController::class, 'store'])->middleware('throttle:mutations')->name('api.v1.drivers.store');
        Route::put('/drivers/{id}', [DriverController::class, 'update'])->middleware('throttle:mutations')->name('api.v1.drivers.update');
        Route::delete('/drivers/{id}', [DriverController::class, 'destroy'])->middleware('throttle:mutations')->name('api.v1.drivers.destroy');
    });

    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
        Route::post('/users/{id}/toggle-status', [UserController::class, 'update'])->middleware('throttle:mutations')->name('api.v1.users.toggle-status');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('throttle:mutations')->name('api.v1.users.destroy');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('api.v1.notifications.read');
    });
});
