<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\DriverController;
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
| Web Routes — API only (frontend serves all HTML/UI)
|--------------------------------------------------------------------------
|
| The backend is a pure API. All GET routes that used to render Blade views
| now redirect to the equivalent frontend page so stale links don't 404.
| POST/PUT/DELETE routes return JSON only.
|
*/

// ── Named redirect stubs — keeps redirect()->route(...) calls in other controllers
//    working now that the backend no longer serves any Blade views. ──────────────

Route::get('/',                   fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/login.php'));
Route::get('/login.php',          fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/login.php'))->name('auth.login');
Route::get('/admin-login.php',    fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-login.php'))->name('auth.admin.login');
Route::get('/logout.php',         fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/login.php'))->name('auth.logout.get');
Route::get('/admin-logout.php',   fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-login.php'))->name('auth.admin.logout.get');
Route::get('/user-dashboard.php', fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/user-dashboard.php'))->name('dashboard.user');
Route::get('/admin-dashboard.php',fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-dashboard.php'))->name('dashboard.admin');
Route::get('/dashboard',          fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/user-dashboard.php'))->name('dashboard');
Route::get('/admin/dashboard',    fn () => redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-dashboard.php'));

// ── Google One-Tap & role setup (JSON) ──
Route::post('/google_auth.php',  [GoogleOnboardingController::class, 'googleAuth'])->middleware('throttle:oauth')->name('auth.google.token');
Route::post('/finalize_role.php',[GoogleOnboardingController::class, 'finalizeRole'])->middleware('throttle:oauth')->name('auth.google.finalize-role');
Route::post('/auth-sync.php',    [GoogleOnboardingController::class, 'authSync'])->middleware('throttle:oauth')->name('auth.sync');

// ── Traditional login/logout (JSON — frontend PHP handles the HTML forms) ──
Route::post('/login.php',       [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login.post');
Route::post('/admin-login.php', [AuthController::class, 'adminLogin'])->middleware('throttle:login')->name('auth.admin.login.post');
Route::post('/logout.php',      [AuthController::class, 'logout'])->name('auth.logout');
Route::post('/admin-logout.php',[AuthController::class, 'adminLogout'])->name('auth.admin.logout');

// ── Google OAuth redirect flow ──
Route::get('/auth/google',          [GoogleAuthController::class, 'redirectToGoogle'])->middleware('throttle:oauth')->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->middleware('throttle:oauth')->name('google.callback');

// ── Password reset (JSON) ──
Route::post('/forgot-password.php', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
Route::post('/reset-password.php',  [PasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');

// ── Redirect old GET password-reset view routes to frontend ──
Route::get('/forgot-password.php', fn () => redirect(app()->call($frontendUrl) . '/forgot_password.php'))->name('password.request');
Route::get('/reset-password.php',  fn () => redirect(app()->call($frontendUrl) . '/reset-password.php'))->name('password.reset');

// ── User API routes (require user session) ──
Route::middleware(['auth.user'])->group(function () {
    Route::post('/register_vehicle.php',         [VehicleController::class, 'store'])->middleware('throttle:mutations')->name('vehicles.store');
    Route::post('/update_vehicle.php',           [VehicleController::class, 'update'])->middleware('throttle:mutations')->name('vehicles.update.post');
    Route::post('/delete_vehicle.php',           [VehicleController::class, 'destroy'])->middleware('throttle:mutations')->name('vehicles.destroy.post');
    Route::post('/vehicle_operations.php',       [VehicleController::class, 'update'])->middleware('throttle:mutations')->name('vehicles.operations.update');
    Route::delete('/vehicle_operations.php',     [VehicleController::class, 'destroy'])->middleware('throttle:mutations')->name('vehicles.destroy.legacy');
    Route::post('/update-owner-info.php',        [OwnerController::class, 'update'])->middleware('throttle:mutations')->name('owners.update.self');
    Route::post('/save_registration_draft.php',  [RegistrationDraftController::class, 'save'])->middleware('throttle:mutations')->name('drafts.save');
    Route::post('/submit_registration.php',      [RegistrationSubmissionController::class, 'submit'])->middleware('throttle:mutations')->name('registration.submit');
    Route::post('/submit-vehicle-registration.php', [RegistrationSubmissionController::class, 'submitVehicleForm'])->middleware('throttle:mutations')->name('registration.submit.vehicle-form');
    Route::post('/search-vehicle.php',           [VehicleController::class, 'search'])->middleware('throttle:search')->name('vehicles.search');
    Route::post('/scan-vehicle.php',             [VehicleController::class, 'scan'])->middleware('throttle:search')->name('vehicles.scan');

    Route::post('/driver_operations.php',        [DriverController::class, 'store'])->middleware('throttle:mutations')->name('drivers.store');
    Route::put('/driver_operations.php/{id}',    [DriverController::class, 'update'])->middleware('throttle:mutations')->name('drivers.update');
    Route::delete('/driver_operations.php/{id}', [DriverController::class, 'destroy'])->middleware('throttle:mutations')->name('drivers.destroy');

    Route::get('/get_notifications.php',         [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/mark_notification_read.php',   [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('notifications.read.payload');
    Route::post('/mark_notification_read.php/{id}', [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('notifications.read');

    Route::resource('vehicles', VehicleController::class)->except(['create', 'store', 'index', 'show', 'destroy']);
    Route::post('/vehicles/{id}/renew', [VehicleController::class, 'renew'])->middleware('throttle:mutations')->name('vehicles.renew');
});

// ── Admin API routes (require admin session) ──
Route::middleware(['auth.admin'])->group(function () {
    Route::post('/admin/owners/{id}',            [AdminOwnerController::class, 'update'])->middleware('throttle:mutations')->name('owners.update');
    Route::get('/get_users.php',                 [AdminUserController::class, 'index'])->name('api.users.index');
    Route::post('/update_user.php',              [AdminUserController::class, 'update'])->middleware('throttle:mutations')->name('users.update');
    Route::post('/delete_user.php',              [AdminUserController::class, 'destroy'])->middleware('throttle:mutations')->name('users.destroy.post');
    Route::delete('/delete_user.php',            [AdminUserController::class, 'destroy'])->middleware('throttle:mutations')->name('users.destroy');
    Route::post('/manage-vehicle-status.php',    [AdminVehicleController::class, 'adminStatusUpdate'])->middleware('throttle:mutations')->name('vehicles.status.admin.update');
    Route::post('/admin/vehicles/bulk-approve',  [AdminVehicleController::class, 'bulkApprove'])->middleware('throttle:mutations')->name('vehicles.bulk.approve');
    Route::post('/edit_report.php',              [AdminReportController::class, 'update'])->middleware('throttle:mutations')->name('reports.update');
    Route::post('/delete_report.php',            [AdminReportController::class, 'destroy'])->middleware('throttle:mutations')->name('reports.destroy.post');
    Route::delete('/delete_report.php',          [AdminReportController::class, 'destroy'])->middleware('throttle:mutations')->name('reports.destroy');

    Route::resource('owners',  AdminOwnerController::class)->except(['index', 'show', 'edit', 'update']);
    Route::resource('users',   AdminUserController::class)->except(['index', 'show', 'update', 'destroy']);
    Route::resource('reports', AdminReportController::class)->except(['index', 'edit', 'update', 'destroy']);
});

// ── Versioned API ──
Route::prefix('api/v1')->middleware(['web'])->group(function () {
    Route::middleware(['auth.user'])->group(function () {
        Route::post('/vehicles/search', [VehicleController::class, 'search'])->middleware('throttle:search')->name('api.v1.vehicles.search');
        Route::post('/vehicles/scan',   [VehicleController::class, 'scan'])->middleware('throttle:search')->name('api.v1.vehicles.scan');
        Route::post('/drivers',         [DriverController::class, 'store'])->middleware('throttle:mutations')->name('api.v1.drivers.store');
        Route::put('/drivers/{id}',     [DriverController::class, 'update'])->middleware('throttle:mutations')->name('api.v1.drivers.update');
        Route::delete('/drivers/{id}',  [DriverController::class, 'destroy'])->middleware('throttle:mutations')->name('api.v1.drivers.destroy');
    });

    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/users',                        [UserController::class, 'index'])->name('api.v1.users.index');
        Route::post('/users/{id}/toggle-status',    [UserController::class, 'update'])->middleware('throttle:mutations')->name('api.v1.users.toggle-status');
        Route::delete('/users/{id}',                [UserController::class, 'destroy'])->middleware('throttle:mutations')->name('api.v1.users.destroy');
        Route::get('/notifications',                [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('/notifications/{id}/read',     [NotificationController::class, 'markRead'])->middleware('throttle:mutations')->name('api.v1.notifications.read');
    });
});
