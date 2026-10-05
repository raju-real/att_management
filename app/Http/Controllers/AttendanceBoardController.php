<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReportService as Report;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Teacher attendance board — the standalone full-page board and the single
 * JSON endpoint that feeds every board (dashboard + standalone page).
 */
class AttendanceBoardController extends Controller
{
    public function index(Request $request)
    {
        return view('attendance.board.page', ['date' => $this->date($request)]);
    }

    /**
     * GET /attendance-board/data?date=YYYY-MM-DD
     */
    public function data(Request $request)
    {
        return response()->json(Report::teacherBoard($this->date($request)));
    }

    protected function date(Request $request): string
    {
        try {
            return Carbon::parse($request->get('date', Carbon::today()->toDateString()))->toDateString();
        } catch (\Throwable) {
            return Carbon::today()->toDateString();
        }
    }
}
