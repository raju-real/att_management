<?php

use App\Http\Controllers\AttendanceBoardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Board Routes
|--------------------------------------------------------------------------
| Standalone full-page teacher attendance board (outside the admin layout),
| e.g. for a lobby TV, plus the one JSON endpoint every board reads from.
| Loaded by RouteServiceProvider with the "web" + "auth" middleware.
*/

Route::controller(AttendanceBoardController::class)->group(function () {
    Route::get('attendance-board', 'index')->name('attendance-board');
    Route::get('attendance-board/data', 'data')->name('attendance-board.data');
});
