<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Teacher;
use App\Services\TeacherProfileService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Attendance → Teacher/Staff Profile: the list of teachers/staff and each
 * one's profile (personal, education, job, class/subject teacher and the
 * month-wise attendance of a year) as a page and an A4 PDF.
 */
class TeacherProfileController extends Controller
{
    public function index(Request $request)
    {
        $institute = selectedInstituteId();

        $teachers = Teacher::with('department:id,name', 'institute:id,name')
            ->forInstitute($institute)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . trim($request->search) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $s)->orWhere('teacher_no', 'like', $s)
                    ->orWhere('mobile', 'like', $s)->orWhere('designation', 'like', $s));
            })
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->orderByRaw('CAST(teacher_no AS UNSIGNED), teacher_no')
            ->paginate(30)
            ->withQueryString();

        $departments = Department::forInstitute($institute)->orderBy('name')->get(['id', 'name']);

        return view('teacher_profile.index', compact('teachers', 'departments'));
    }

    /** GET /teacher-profiles/{id}?year=2026[&export=pdf] */
    public function show(Request $request, $id)
    {
        $teacher = Teacher::with('department.shift', 'institute')->findOrFail($id); // scoped to the institute
        $year    = (int) $request->input('year', Carbon::today()->year);
        if ($year < 2000 || $year > Carbon::today()->year + 1) {
            $year = Carbon::today()->year;
        }

        $attendance = TeacherProfileService::yearly($teacher, $year);
        $school     = schoolInfo($teacher->institute_id);

        if ($request->get('export') === 'pdf') {
            return $this->pdf($teacher, $attendance, $school);
        }

        return view('teacher_profile.show', compact('teacher', 'attendance', 'school', 'year'));
    }

    protected function pdf(Teacher $teacher, array $attendance, object $school)
    {
        $tmp = storage_path('app/mpdf');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($tmp);

        $mpdf = null;
        \niklasravnsborg\LaravelPdf\Facades\Pdf::loadHTML('', [
            'format'        => 'A4',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 8,
            'margin_bottom' => 12,
            'margin_footer' => 5,
            'tempDir'       => $tmp,
            'instanceConfigurator' => function ($instance) use (&$mpdf) {
                $mpdf = $instance;
            },
        ]);

        $mpdf->SetTitle($school->name . ' - Teacher/Staff Profile - ' . $teacher->name);
        $mpdf->SetAuthor($school->name);
        $mpdf->SetHTMLFooter(view('teacher_profile._pdf_footer', compact('teacher', 'school'))->render());
        $mpdf->WriteHTML(view('teacher_profile._sheet', [
            'teacher' => $teacher, 'attendance' => $attendance, 'school' => $school, 'mode' => 'pdf',
        ])->render());

        $file = 'profile_' . preg_replace('/[^A-Za-z0-9]+/', '_', $teacher->name ?: 'teacher') . '_' . $teacher->teacher_no . '_' . $attendance['year'] . '.pdf';

        return response($mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $file . '"',
        ]);
    }
}
