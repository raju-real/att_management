<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\Teacher;
use App\Rules\UniqueDevicePin;
use App\Services\DeviceActivityService;
use App\Services\DeviceSyncService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
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
        $query = Teacher::with('department.shift');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('teacher_no')) {
            $query->where('teacher_no', 'like', '%' . $request->teacher_no . '%');
        }
        if ($request->filled('designation')) {
            $query->where('designation', 'like', '%' . $request->designation . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $teachers    = $query->latest('teacher_no')->paginate(25);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        return view('teacher.teacher_list', compact('teachers', 'departments'));
    }

    /**
     * Push ALL teachers to ALL active devices — synchronous, no background job.
     */
    public function pushToDevice()
    {
        $result = $this->activity->pushTeachersToAllDevices();

        if ($result['deviceCount'] === 0) {
            return redirect()->back()->with(dangerMessage('danger', 'No active devices found for teachers.'));
        }

        $msg = implode("\n", $result['messages']);
        return redirect()->back()->with(successMessage('success',
            "{$result['total']} teacher(s) processed across {$result['deviceCount']} device(s).\n{$msg}"));
    }

    // ───────────────────────────── Import (CSV / Excel) ─────────────────────────────

    public function import()
    {
        $departments = Department::where('status', 'active')->with('shift:id,title,in_time,out_time')->orderBy('name')->get();
        return view('teacher.import', compact('departments'));
    }

    public function importDemo()
    {
        $department = Department::orderBy('name')->value('name') ?? 'Science';

        $content  = "\xEF\xBB\xBF" . "teacher_no,name,email,mobile,designation,department\n";
        $content .= "101,Abdul Karim,karim@example.com,01711000001,Senior Teacher,{$department}\n";
        $content .= "102,Nasrin Akter,,01711000002,Assistant Teacher,\n";

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="teacher_import_demo.csv"',
        ]);
    }

    public function upload(Request $request)
    {
        $this->validate($request, [
            'file'          => 'required|file|mimes:xls,xlsx,csv,txt|max:5120',
            'department_id' => ['nullable', Rule::exists('departments', 'id')->whereNull('deleted_at')],
        ]);

        $import = new \App\Imports\TeachersImport($request->department_id ? (int) $request->department_id : null);

        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['file' => 'Error reading file: ' . $e->getMessage()]);
        }

        $summary = "{$import->created} teacher(s) created, {$import->updated} updated"
            . (count($import->skipped) ? ', ' . count($import->skipped) . ' skipped' : '') . '.';

        if (($import->created + $import->updated) > 0) {
            $summary .= ' Use "Push to Device" to send new/updated teachers to the fingerprint devices.';
        }

        return redirect()->route('teachers.import')
            ->with(successMessage(count($import->skipped) ? 'warning' : 'success', $summary))
            ->with('import_skipped', array_slice($import->skipped, 0, 200));
    }

    public function create()
    {
        $route       = route('teachers.store');
        $departments = $this->departmentOptions();
        return view('teacher.teacher_add_edit', compact('route', 'departments'));
    }

    public function store(Request $request)
    {
        $this->validate($request, $this->rules($request), $this->messages());

        $teacher = new Teacher();
        // The device PIN — user-typed so it can match a PIN already enrolled
        // on a device (e.g. resolving an Unmatched Attendance / Pull Users
        // record) instead of always minting a brand new number.
        $teacher->teacher_no    = trim($request->teacher_no);
        $teacher->name          = $request->name;
        $teacher->email         = $request->email;
        $teacher->mobile        = $request->mobile;
        $teacher->designation   = $request->designation;
        $teacher->department_id = $request->department_id;

        if ($request->hasFile('image')) {
            $teacher->image = uploadImage($request->file('image'), 'teachers');
        }

        $teacher->save();

        // Historical attendance punches that came in with this PIN before we
        // knew who it belonged to — re-attach them now.
        $this->healUnmatchedAttendance($teacher);

        // Push this one teacher to every device that should have them —
        // no need to re-run the bulk "Push to Device" for a single addition.
        $this->deviceSync->pushOne('teacher', (string) $teacher->teacher_no, $teacher->name);

        return redirect()->route('teachers.index')->with(successMessage());
    }

    public function edit($teacher_no)
    {
        $teacher     = Teacher::whereTeacherNo($teacher_no)->firstOrFail();
        $route       = route('teachers.update', $teacher->id);
        $departments = $this->departmentOptions($teacher->department_id);
        return view('teacher.teacher_add_edit', compact('teacher', 'route', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, $this->rules($request, $id), $this->messages());

        $teacher                = Teacher::findOrFail($id);
        $oldTeacherNo           = $teacher->teacher_no;
        $teacher->teacher_no    = trim($request->teacher_no);
        $teacher->name          = $request->name;
        $teacher->email         = $request->email;
        $teacher->mobile        = $request->mobile;
        $teacher->designation   = $request->designation;
        $teacher->department_id = $request->department_id;

        if ($request->hasFile('image')) {
            if ($teacher->image !== null && file_exists($teacher->image)) {
                unlink($teacher->image);
            }
            $teacher->image = uploadImage($request->file('image'), 'teachers');
        }

        if ($request->has('remove_image') && $request->remove_image == '1') {
            if ($teacher->image !== null && file_exists($teacher->image)) {
                unlink($teacher->image);
            }
            $teacher->image = null;
        }

        $teacher->save();

        // If the device PIN itself changed, remove the old PIN from devices
        // (it no longer belongs to anyone) before pushing the new one.
        if ($oldTeacherNo !== $teacher->teacher_no) {
            $this->deviceSync->deleteOne('teacher', (string) $oldTeacherNo);
        }

        // Historical attendance punches that came in with this PIN before we
        // knew who it belonged to — re-attach them now (covers the case
        // where the PIN was just changed to match an existing device PIN).
        $this->healUnmatchedAttendance($teacher);

        // Keep the device's copy of this teacher's name in sync.
        $this->deviceSync->pushOne('teacher', (string) $teacher->teacher_no, $teacher->name);

        return redirect()->route('teachers.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        if ($teacher->image !== null && file_exists($teacher->image)) {
            unlink($teacher->image);
        }

        $this->deviceSync->deleteOne('teacher', (string) $teacher->teacher_no);

        $teacher->delete();
        return redirect()->route('teachers.index')->with(deleteMessage());
    }

    protected function rules(Request $request, $ignoreId = null): array
    {
        $unique = fn () => Rule::unique('teachers')->whereNull('deleted_at')->ignore($ignoreId);

        return [
            'name'          => ['required', 'string', 'max:100'],
            'teacher_no'    => ['required', 'regex:/^[0-9]{1,20}$/', Rule::unique('teachers')->ignore($ignoreId), new UniqueDevicePin('teacher')],
            'email'         => ['nullable', 'email', 'max:100', $unique()],
            'mobile'        => ['nullable', 'regex:/^[0-9+\-\s]{6,20}$/', $unique()],
            'designation'   => ['nullable', 'string', 'max:100'],
            'department_id' => ['required', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'image'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'department_id.required' => 'Please select a department (its shift sets the in/out time).',
            'teacher_no.regex'       => 'Device ID must be a number of 1 to 20 digits (fingerprint devices only accept numeric IDs).',
            'teacher_no.unique'      => 'This device ID already belongs to another teacher (including deleted teachers).',
            'mobile.regex'           => 'Enter a valid phone number (digits, +, - and spaces only).',
        ];
    }

    /**
     * Active departments (with their shift for the timing hint), plus the
     * teacher's current one on edit even if it has since been deactivated.
     */
    protected function departmentOptions(?int $currentId = null)
    {
        return Department::with('shift:id,title,in_time,out_time')
            ->where(fn ($q) => $q->where('status', 'active')
                ->when($currentId, fn ($q) => $q->orWhere('id', $currentId)))
            ->orderBy('name')
            ->get(['id', 'name', 'shift_id', 'status']);
    }

    /**
     * Re-attach any attendance punches that arrived under this teacher's PIN
     * before the teacher existed in our DB (stored under `unmatched_pin`).
     */
    protected function healUnmatchedAttendance(Teacher $teacher): void
    {
        AttendanceLog::where('unmatched_pin', $teacher->teacher_no)
            ->whereNull('teacher_no')
            ->whereNull('student_no')
            ->get()
            ->each(function (AttendanceLog $log) use ($teacher) {
                try {
                    $log->update([
                        'teacher_no'    => $teacher->teacher_no,
                        'user_type'     => 'teacher',
                        'unmatched_pin' => null,
                    ]);
                } catch (\Throwable) {
                    // Unique constraint clash against an existing row for this
                    // teacher/time/device — leave that one under review rather
                    // than fail the whole batch.
                }
            });
    }
}
