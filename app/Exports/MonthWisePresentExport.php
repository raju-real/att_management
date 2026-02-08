<?php
namespace App\Exports;

use App\Services\AttendanceService;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;

class MonthWisePresentExport implements FromView, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $attendance_reports = AttendanceService::monthWisePresentReport($this->filters);

        $from_date = $this->filters['from_date'] ?? '';
        $to_date   = $this->filters['to_date'] ?? $from_date;
        $user_type = $this->filters['user_type'] ?? '';
        $student_id = $this->filters['student_id'] ?? '';
        $teacher_no = $this->filters['teacher_no'] ?? '';

        return view('exports.month_wise_present', compact('attendance_reports', 'from_date', 'to_date','user_type','student_id','teacher_no'));
    }
}
