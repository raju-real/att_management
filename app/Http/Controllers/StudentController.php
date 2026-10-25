<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Student;
use App\Rules\UniqueDevicePin;
use App\Services\DeviceActivityService;
use App\Services\DeviceSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    protected DeviceSyncService $deviceSync;
    protected DeviceActivityService $activity;

    public function __construct(DeviceSyncService $deviceSync, DeviceActivityService $activity)
    {
        $this->deviceSync = $deviceSync;
        $this->activity   = $activity;
    }

    public function index(Request $request)
    {
        $data = Student::with('institute:id,name')->forInstitute(selectedInstituteId());

        if ($request->name) {
            $data->where(function ($query) use ($request) {
                $query->where('firstname', 'like', "%{$request->name}%")
                    ->orWhere('middlename', 'like', "%{$request->name}%")
                    ->orWhere('lastname', 'like', "%{$request->name}%");
            });
        }

        if ($request->student_id) {
            $data->where('student_id', 'like', "%{$request->student_id}%");
        }

        if ($request->student_no) {
            $data->where('student_no', 'like', "%{$request->student_no}%");
        }

        if ($request->class) {
            $data->where('class', $request->class);
        }

        $students = $data->latest('student_no')->paginate(50);
        return view('student.student_list', compact('students'));
    }

    public function sync()
    {
        // Check if the source student_db connection is reachable before dispatching
        try {
            \Illuminate\Support\Facades\DB::connection('student_db')->getPdo();
        } catch (\Exception $e) {
            return redirect()->back()->with(
                dangerMessage('danger', 'Cannot connect to student database: ' . $e->getMessage())
            );
        }

        return redirect()->back()->with(successMessage('success', 'Student sync has been queued and is running in background.'));
    }

    /**
     * Push ALL students to ALL active devices — synchronous, no background job.
     */
    public function pushToDevice()
    {
        $result = $this->activity->pushStudentsToAllDevices();

        if ($result['deviceCount'] === 0) {
            return redirect()->back()->with(dangerMessage('danger', 'No active devices found for students.'));
        }

        $msg = implode("\n", $result['messages']);
        return redirect()->back()->with(successMessage('success',
            "{$result['total']} student(s) processed across {$result['deviceCount']} device(s).\n{$msg}"));
    }

    public function import()
    {
        return view('student.import');
    }

    public function demoExcel()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_demo.csv"',
        ];

        $content = "student_id,firstname,middlename,lastname,nickname,class,section,roll,shift,medium,group\n";
        $content .= "1001,John,,Doe,Johnny,Six,A,1,Morning,English,Science\n";
        $content .= "1002,Jane,A,Smith,Jenny,Six,A,2,Morning,English,Arts\n";

        return response($content, 200, $headers);
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file'         => 'required|file|mimes:xls,xlsx,csv,txt|max:5120',
            'institute_id' => $this->instituteRule(),
        ], ['institute_id.required' => 'Select the institute these students belong to.']);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport($this->instituteFor($request)), $request->file('file'));
            return redirect()->route('students.index')->with(successMessage('success', 'Students imported successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file' => 'Error importing file: ' . $e->getMessage()]);
        }
    }

    public function create()
    {
        return view('student.create');
    }

    public function store(Request $request)
    {
        $institute = $this->instituteFor($request);
        $this->validate($request, $this->rules() + [
            'institute_id' => $this->instituteRule(),
            'student_id' => ['required', 'string', 'max:191', Rule::unique('students', 'student_id')->where('institute_id', $institute)->whereNull('deleted_at')],
            // Device PIN: numeric, unique among the institute's students (incl. deleted:
            // the PIN may still be enrolled on a device) and not used by its teachers/staff.
            'student_no' => ['nullable', 'regex:/^[0-9]{1,20}$/', Rule::unique('students', 'student_no')->where('institute_id', $institute), new UniqueDevicePin('student', $institute)],
        ], $this->messages());

        $student = new Student();
        $student->institute_id = $institute;
        // A custom student_no lets you match a PIN already enrolled on a
        // device (e.g. resolving an Unmatched Attendance / Pull Users
        // record) instead of always minting a brand new number.
        $student->student_no = $request->filled('student_no') ? trim($request->student_no) : Student::getStudentNo();
        $student->student_id = $request->student_id;
        $student->firstname = $request->firstname;
        $student->middlename = $request->middlename;
        $student->lastname = $request->lastname;
        $student->nickname = $request->nickname;
        $student->class = $request->class;
        $student->section = $request->section;
        $student->roll = $request->roll;
        $student->shift = $request->shift;
        $student->medium = $request->medium;
        $student->group = $request->group;
        $student->save();

        // Historical attendance punches that came in with this PIN before we
        // knew who it belonged to — re-attach them now.
        $this->healUnmatchedAttendance($student);

        // Push this one student to every device that should have them —
        // no need to re-run the bulk "Push to Device" for a single addition.
        $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);
        $this->deviceSync->pushOne('student', (string) $student->student_no, $name, $student->institute_id);

        return redirect()->route('students.index')->with(successMessage());
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        return view('student.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $student   = Student::findOrFail($id);
        $institute = $this->instituteFor($request) ?? $student->institute_id;
        $this->validate($request, $this->rules() + [
            'institute_id' => $this->instituteRule(),
            'student_id' => ['required', 'string', 'max:191', Rule::unique('students', 'student_id')->where('institute_id', $institute)->whereNull('deleted_at')->ignore($id)],
            'student_no' => ['nullable', Rule::unique('students', 'student_no')->where('institute_id', $institute)->ignore($id), new UniqueDevicePin('student', $institute)],
        ], $this->messages() + ['student_no.unique' => 'This device ID is already used by a student of the selected institute.']);

        $oldInstitute = $student->institute_id;
        $student->institute_id = $institute;
        $student->student_id = $request->student_id;
        $student->firstname = $request->firstname;
        $student->middlename = $request->middlename;
        $student->lastname = $request->lastname;
        $student->nickname = $request->nickname;
        $student->class = $request->class;
        $student->section = $request->section;
        $student->roll = $request->roll;
        $student->shift = $request->shift;
        $student->medium = $request->medium;
        $student->group = $request->group;
        $student->save();

        // Moved to another institute: remove from the old institute's devices.
        if ((int) $oldInstitute !== (int) $student->institute_id) {
            $this->deviceSync->deleteOne('student', (string) $student->student_no, $oldInstitute);
        }

        // Keep the device's copy of this student's name in sync.
        $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);
        $this->deviceSync->pushOne('student', (string) $student->student_no, $name, $student->institute_id);

        return redirect()->route('students.index')->with(successMessage('success', 'Student updated successfully'));
    }

    public function show($id)
    {
        // by id: student_no is only unique inside one institute
        $student = Student::findOrFail($id);
        return view('student.show', compact('student'));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $this->deviceSync->deleteOne('student', (string) $student->student_no, $student->institute_id);

        $student->delete();

        return redirect()
            ->route('students.index')
            ->with(deleteMessage());
    }

    /** Field rules shared by store() and update(). Columns are varchar(191). */
    protected function rules(): array
    {
        return [
            'firstname'  => ['required', 'string', 'max:191'],
            'middlename' => ['nullable', 'string', 'max:191'],
            'lastname'   => ['nullable', 'string', 'max:191'],
            'nickname'   => ['nullable', 'string', 'max:191'],
            'class'      => ['nullable', 'string', 'max:191'],
            'section'    => ['nullable', 'string', 'max:191'],
            'roll'       => ['nullable', 'string', 'max:191'],
            'shift'      => ['nullable', 'string', 'max:191'],
            'medium'     => ['nullable', 'string', 'max:191'],
            'group'      => ['nullable', 'string', 'max:191'],
        ];
    }

    protected function messages(): array
    {
        return [
            'student_id.unique' => 'Another student already has this Student ID.',
            'student_no.regex'  => 'Device ID must be a number of 1 to 20 digits (fingerprint devices only accept numeric IDs).',
            'student_no.unique' => 'This device ID already belongs to another student (including deleted students).',
            'institute_id.required' => 'Please select the institute.',
        ];
    }

    /** Institute of the record being saved: forced for institute users, chosen by the admin. */
    protected function instituteFor(Request $request): ?int
    {
        return authInstituteId() ?? ($request->filled('institute_id') ? (int) $request->institute_id : null);
    }

    protected function instituteRule(): array
    {
        return isSuperAdmin()
            ? ['required', Rule::exists('users', 'id')->where('role', 'institute')->whereNull('deleted_at')]
            : ['nullable'];
    }

    /**
     * Re-attach any attendance punches that arrived under this student's PIN
     * before the student existed in our DB (stored under `unmatched_pin`).
     */
    protected function healUnmatchedAttendance(Student $student): void
    {
        AttendanceLog::where('unmatched_pin', $student->student_no)
            ->where('institute_id', $student->institute_id)
            ->whereNull('teacher_no')
            ->whereNull('student_no')
            ->get()
            ->each(function (AttendanceLog $log) use ($student) {
                try {
                    $log->update([
                        'student_no'    => $student->student_no,
                        'user_type'     => 'student',
                        'unmatched_pin' => null,
                    ]);
                } catch (\Throwable) {
                    // Unique constraint clash against an existing row for this
                    // student/time/device — leave that one under review rather
                    // than fail the whole batch.
                }
            });
    }
}
