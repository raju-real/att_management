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

Route::middleware('auth')->group(function(){
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
        Route::delete('remove-users/{device_id}','removeUsers')->name('devices.remove-users');
    });
    // Manage Student
    Route::resource('students', StudentController::class);
    // Manage Teacher
    Route::resource('teachers', TeacherController::class);
    // Attendance manage
    Route::controller(AttendanceController::class)->group(function () {
        Route::get('present-logs', 'presentLogs')->name('present-logs');
        Route::get('attendance-summery', 'attendanceSummery')->name('attendance-summery');
        // Report
        Route::get('date-wise-present-report','dateWisePresentReport')->name('date-wise-present-report');
        Route::get('month-wise-user-summery','monthWiseUserSummery')->name('month-wise-user-summery');
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
    });
});

// Logout
Route::get('logout', function () {
    Auth::logout();
    Session::reflash();
    return redirect()->route('home');
})->name('logout');

