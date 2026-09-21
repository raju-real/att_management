<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Student;
use App\Services\DeviceSyncService;
use App\Services\ZkTecoService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    protected ZkTecoService $zkService;
    protected DeviceSyncService $deviceSync;

    public function __construct(ZkTecoService $zkService, DeviceSyncService $deviceSync)
    {
        $this->zkService  = $zkService;
        $this->deviceSync = $deviceSync;
    }

    public function index(Request $request)
    {
        $data = Student::query();

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

        \App\Jobs\SyncStudentFromDbJob::dispatch();

        return redirect()->back()->with(successMessage('success', 'Student sync has been queued and is running in background.'));
    }

    /**
     * Push ALL students to ALL active devices — synchronous, no background job.
     */
    public function pushToDevice()
    {
        $devices = Device::where('status', 'active')
            ->whereIn('device_for', ['student', 'student_teacher'])
            ->get();

        if ($devices->isEmpty()) {
            return redirect()->back()->with(dangerMessage('danger', 'No active devices found for students.'));
        }

        $students = Student::all();
        $total    = 0;
        $messages = [];

        foreach ($devices as $device) {
            if ($device->use_push_mode) {
                $queued = 0;
                foreach ($students as $student) {
                    $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname) ?: 'Student';
                    DeviceCommand::queue(
                        $device->id,
                        DeviceCommand::setUserCommand((string) $student->student_no, $name)
                    );
                    $queued++;
                }
                $messages[] = "✓ [{$device->name}] Push Mode: {$queued} students queued (sync within 30 sec)";
                $total += $queued;
            } else {
                $zk = $this->zkService->connect($device);
                if (! $zk) {
                    $messages[] = "✗ [{$device->name}] TCP connect failed — check IP/network";
                    continue;
                }
                $pushed = 0;
                foreach ($students as $student) {
                    try {
                        $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname) ?: 'Student';
                        $this->zkService->pushUser($zk, (string) $student->student_no, $name);
                        $pushed++;
                    } catch (\Throwable) {}
                }
                $this->zkService->disconnect($zk);
                $messages[] = "✓ [{$device->name}] TCP: {$pushed} students pushed directly";
                $total += $pushed;
            }
        }

        $msg = implode("\n", $messages);
        return redirect()->back()->with(successMessage('success',
            "{$total} student(s) processed across " . $devices->count() . " device(s).\n{$msg}"));
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
            'file' => 'required|mimes:xls,xlsx,csv|max:2048'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StudentsImport, $request->file('file'));
            return redirect()->route('students.index')->with(successMessage('success', 'Students imported successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error importing file: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('student.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'student_id' => 'required|unique:students,student_id',
            'student_no' => 'nullable|string|max:255|unique:students,student_no',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'roll' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'medium' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        $student = new Student();
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
        $this->deviceSync->pushOne('student', (string) $student->student_no, $name);

        return redirect()->route('students.index')->with(successMessage());
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        return view('student.edit', compact('student'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'student_id' => 'required|unique:students,student_id,' . $id,
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'class' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'roll' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'medium' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
        ]);

        $student = Student::findOrFail($id);
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

        // Keep the device's copy of this student's name in sync.
        $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);
        $this->deviceSync->pushOne('student', (string) $student->student_no, $name);

        return redirect()->route('students.index')->with(successMessage('success', 'Student updated successfully'));
    }

    public function show($student_no)
    {
        $student = Student::where('student_no', $student_no)->firstOrFail();
        return view('student.show', compact('student'));
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $this->deviceSync->deleteOne('student', (string) $student->student_no);

        $student->delete();

        return redirect()
            ->route('students.index')
            ->with(deleteMessage());
    }

    /**
     * Re-attach any attendance punches that arrived under this student's PIN
     * before the student existed in our DB (stored under `unmatched_pin`).
     */
    protected function healUnmatchedAttendance(Student $student): void
    {
        $name = showStudentFullName($student->firstname, $student->middlename, $student->lastname);

        AttendanceLog::where('unmatched_pin', $student->student_no)
            ->whereNull('teacher_no')
            ->whereNull('student_no')
            ->get()
            ->each(function (AttendanceLog $log) use ($student, $name) {
                try {
                    $log->update([
                        'student_no'    => $student->student_no,
                        'user_type'     => 'student',
                        'name'          => $name,
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
