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
     * GET /attendance-board/data?date=YYYY-MM-DD[&per_page=24]
     * A valid per_page is remembered for this user (see Report::boardPerPage).
     */
    public function data(Request $request)
    {
        // Optional filters: department only, shift only, or both.
        $filters = [
            'department_id' => ctype_digit((string) $request->query('department_id')) ? (int) $request->query('department_id') : null,
            'shift_id'      => ctype_digit((string) $request->query('shift_id')) ? (int) $request->query('shift_id') : null,
        ];

        $payload = Report::teacherBoard($this->date($request), $filters);
        $payload['per_page'] = Report::boardPerPage($request->query('per_page'));

        return response()->json($payload);
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
