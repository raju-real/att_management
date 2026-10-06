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
use App\Http\Controllers\ZktecoAdmsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| iClock / ZkTeco ADMS Push Protocol (PUBLIC — no auth)
|--------------------------------------------------------------------------
*/
Route::controller(ZktecoAdmsController::class)->prefix('iclock')->group(function() {
    Route::match(['GET', 'POST'], 'cdata', 'cdata');
    Route::get('getrequest', 'getRequest');
    Route::post('devicecmd', 'deviceCommand');
});


Route::view('/', 'auth.admin_login')->name('home');
Route::post('admin-login', AdminLogin::class)->middleware('throttle:admin-login')->name('admin-login');
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
    // Live push monitor (device check-ins + incoming punches)
    Route::get('devices-monitor', [\App\Http\Controllers\DeviceMonitorController::class, 'index'])->name('devices-monitor');
    Route::get('devices-monitor/data', [\App\Http\Controllers\DeviceMonitorController::class, 'data'])->name('devices-monitor.data');
    Route::resource('devices', DeviceController::class);
    Route::controller(DeviceController::class)->group(function () {
        Route::delete('remove-users/{device_id}', 'removeUsers')->name('devices.remove-users');
        Route::get('test-connection/{device_id}', 'testConnection')->name('devices.test-connection');
        Route::get('test-connection-json/{device_id}', 'testConnectionJson')->name('devices.test-connection-json');
        Route::get('device-users/{device_id}', 'getUsers')->name('devices.users');
        Route::get('device-setup-guide', 'setupGuide')->name('devices.setup-guide');
        // Push students / teachers to specific device
        Route::post('devices/{device_id}/push-students', 'pushStudents')->name('devices.push-students');
        Route::post('devices/{device_id}/push-teachers', 'pushTeachers')->name('devices.push-teachers');
        Route::post('devices/{device_id}/pull-attendance', 'pullAttendance')->name('devices.pull-attendance');
        Route::post('devices/{device_id}/pull-users', 'pullUsers')->name('devices.pull-users');
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
    Route::get('teachers/import/demo', [TeacherController::class, 'importDemo'])->name('teachers.import.demo');
    Route::get('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::post('teachers/upload', [TeacherController::class, 'upload'])->name('teachers.upload');
    Route::resource('teachers', TeacherController::class);
    // Department & Shift (teacher timing config)
    Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except('show');
    Route::resource('shifts', \App\Http\Controllers\ShiftController::class)->except('show');
    // Attendance manage
    Route::controller(AttendanceController::class)->group(function () {
        Route::post('attendance-sync-background', 'syncBackground')->name('attendance.sync.background');
        Route::get('attendance-logs', 'logs')->name('attendance.logs');
        Route::get('attendance-monthly-summary', 'monthlySummary')->name('attendance.monthly-summary');
        Route::get('attendance-unmatched', 'unmatched')->name('attendance.unmatched');
    });
    // Legacy attendance/report URLs → new pages (names kept so old links still resolve)
    Route::redirect('present-logs', '/attendance-logs')->name('present-logs');
    Route::redirect('attendance-summery', '/attendance-logs')->name('attendance-summery');
    Route::redirect('month-wise-present-report', '/attendance-logs')->name('month-wise-present-report');
    Route::redirect('month-wise-user-summery', '/attendance-monthly-summary')->name('month-wise-user-summery');
    // Device Activity controller
    Route::controller(DeviceActivityController::class)->group(function () {
        Route::view('/commands/activities', 'configuration.device_activities')->name('commands.activities');
        Route::post('/commands/sync-users', 'syncUsers')->name('commands.sync.users');                   // USERS
        Route::post('/commands/sync-attendance', 'syncAttendance')->name('commands.sync.attendance');    // ATTENDANCE
        Route::post('/commands/clear-attendance', 'clearAttendance')->name('commands.clear.attendance'); // CLEAR ATTENDANCE
        Route::post('/commands/delete-user', 'deleteUserFromDevice')->name('commands.delete.user');      // DELETE USER FROM DEVICE
    });
    // Settings
    Route::controller(SettingController::class)->group(function () {
        Route::get('site-settings', 'siteSettings')->name('site-settings');
        Route::put('update-site-settings', 'updateSiteSettings')->name('update-site-settings');
        Route::get('fee-settings', 'feeSettings')->name('fee-settings');
        Route::put('update-fee-settings', 'updateFeeSettings')->name('update-fee-settings');
    });

});

// Logout
Route::get('logout', function (\Illuminate\Http\Request $request) {
    if ($user = Auth::user()) {
        $user->last_logout_at = now();
        $user->save();
    }
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');
