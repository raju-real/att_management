<?php

use App\Http\Controllers\AdminLogin;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceActivityController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'auth.admin_login')->name('home');
//Route::post('admin-login', AdminLogin::class)->middleware('throttle:5,1')->name('admin-login');
Route::post('admin-login', AdminLogin::class)->name('admin-login');
Route::view('permission-denied', 'permission_denied')->name('permission-denied');

Route::middleware('auth')->group(function () {
    // ===================================================================
    // ONLY FOR ADMIN ROUTES
    // ===================================================================
    // Dashboard
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
    });
    // Profile
    Route::controller(ProfileController::class)->group(function () {
        Route::get('profile', 'profile')->name('profile');
        Route::put('update-profile', 'updateProfile')->name('update-profile');
    });
    // Devices
    Route::resource('devices', DeviceController::class);
    Route::controller(DeviceController::class)->group(function () {
        Route::delete('remove-users/{device_id}', 'removeUsers')->name('devices.remove-users');
        Route::get('test-connection/{device_id}', 'testConnection')->name('devices.test-connection');
    });
    // Manage Student
    Route::get('students/sync', [StudentController::class, 'sync'])->name('students.sync');
    Route::get('students/push-to-device', [StudentController::class, 'pushToDevice'])->name('students.push-to-device');
    Route::get('students/import/demo', [StudentController::class, 'demoExcel'])->name('students.import.demo');
    Route::get('students/import', [StudentController::class, 'import'])->name('students.import');
    Route::post('students/upload', [StudentController::class, 'upload'])->name('students.upload');
    Route::resource('students', StudentController::class);
    // Manage Teacher
    Route::get('teachers/push-to-device', [TeacherController::class, 'pushToDevice'])->name('teachers.push-to-device');
    Route::resource('teachers', TeacherController::class);
    // Attendance manage
    Route::controller(AttendanceController::class)->group(function () {
        Route::post('attendance-sync-background', 'syncBackground')->name('attendance.sync.background');
        Route::get('present-logs', 'presentLogs')->name('present-logs');
        Route::get('attendance-summery', 'attendanceSummery')->name('attendance-summery');
        // Report
        Route::get('month-wise-present-report', 'monthWisePresentReport')->name('month-wise-present-report');
        Route::get('month-wise-user-summery', 'monthWiseUserSummery')->name('month-wise-user-summery');
        Route::get('track-attendance-location', 'trackLocation')->name('track-attendance-location');
    });
    // Device Activity controller
    Route::controller(DeviceActivityController::class)->group(function () {
        Route::view('/commands/activities', 'configuration.device_activities')->name('commands.activities');
        Route::post('/commands/sync-users', 'syncUsers')->name('commands.sync.users'); // USERS
        Route::post('/commands/sync-attendance', 'syncAttendance')->name('commands.sync.attendance'); // ATTENDANCE
        Route::post('/commands/clear-attendance', 'clearAttendance')->name('commands.clear.attendance'); // CLEAR ATTENDANCE
        Route::post('/commands/delete-user', 'deleteUserFromDevice')->name('commands.delete.user'); // DELETE USER FROM DEVICE
    });
    // Settings
    Route::controller(SettingController::class)->group(function () {
        Route::get('site-settings', 'siteSettings')->name('site-settings');
        Route::put('update-site-settings', 'updateSiteSettings')->name('update-site-settings');
        Route::get('fee-settings', 'feeSettings')->name('fee-settings');
        Route::put('update-fee-settings', 'updateFeeSettings')->name('update-fee-settings');
    });

    // Gateway Settings
    Route::controller(\App\Http\Controllers\PaymentGatewayController::class)->group(function () {
        Route::get('gateway-settings', 'index')->name('gateway-settings');
        Route::put('gateway-settings', 'update')->name('gateway-settings.update');
    });

    // Fee Lots
    Route::resource('fee-lots', \App\Http\Controllers\FeeLotController::class);

    // Payments
    Route::controller(\App\Http\Controllers\PaymentController::class)->prefix('payment')->group(function () {
        Route::get('initiate/{id}', 'initiate')->name('payment.initiate');
        Route::post('success', 'success')->name('payment.success');
        Route::post('fail', 'fail')->name('payment.fail');
        Route::post('cancel', 'cancel')->name('payment.cancel');
        Route::post('ipn', 'ipn')->name('payment.ipn');
        Route::get('transaction/{transactionId}', 'transactionDetails')->name('payment.transaction.details');
        Route::post('transaction/{transactionId}/refund', 'refund')->name('payment.transaction.refund');
    });
});

// Logout
Route::get('logout', function () {
    Auth::logout();
    Session::reflash();
    return redirect()->route('home');
})->name('logout');
