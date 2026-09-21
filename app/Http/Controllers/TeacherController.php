<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\DeviceSyncService;
use App\Services\ZkTecoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
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
        $query = Teacher::with('department', 'shift');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('teacher_no')) {
            $query->where('teacher_no', 'like', '%' . $request->teacher_no . '%');
        }
        if ($request->filled('designation')) {
            $query->where('designation', 'like', '%' . $request->designation . '%');
        }

        $teachers = $query->latest('teacher_no')->paginate(25);
        return view('teacher.teacher_list', compact('teachers'));
    }

    /**
     * Push ALL teachers to ALL active devices — synchronous, no background job.
     */
    public function pushToDevice()
    {
        $devices  = Device::where('status', 'active')
            ->whereIn('device_for', ['teacher', 'student_teacher'])
            ->get();

        if ($devices->isEmpty()) {
            return redirect()->back()->with(dangerMessage('danger', 'No active devices found for teachers.'));
        }

        $teachers = Teacher::all();
        $total    = 0;
        $messages = [];

        foreach ($devices as $device) {
            if ($device->use_push_mode) {
                $queued = 0;
                foreach ($teachers as $teacher) {
                    DeviceCommand::queue(
                        $device->id,
                        DeviceCommand::setUserCommand((string) $teacher->teacher_no, $teacher->name ?? 'Teacher')
                    );
                    $queued++;
                }
                $messages[] = "✓ [{$device->name}] Push Mode: {$queued} teachers queued (sync within 30 sec)";
                $total += $queued;
            } else {
                $zk = $this->zkService->connect($device);
                if (! $zk) {
                    $messages[] = "✗ [{$device->name}] TCP connect failed — check IP/network";
                    continue;
                }
                $pushed = 0;
                foreach ($teachers as $teacher) {
                    try {
                        $this->zkService->pushUser($zk, (string) $teacher->teacher_no, $teacher->name ?? 'Teacher');
                        $pushed++;
                    } catch (\Throwable) {}
                }
                $this->zkService->disconnect($zk);
                $messages[] = "✓ [{$device->name}] TCP: {$pushed} teachers pushed directly";
                $total += $pushed;
            }
        }

        $msg = implode("\n", $messages);
        return redirect()->back()->with(successMessage('success',
            "{$total} teacher(s) processed across " . $devices->count() . " device(s).\n{$msg}"));
    }

    public function create()
    {
        $route       = route('teachers.store');
        $departments = \App\Models\Department::where('status', 'active')->orderBy('name')->get();
        $shifts      = \App\Models\Shift::where('status', 'active')->get();
        return view('teacher.teacher_add_edit', compact('route', 'departments', 'shifts'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'teacher_no' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'email' => [
                'nullable', 'email', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'mobile' => [
                'nullable', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at'),
            ],
            'designation'   => ['nullable', 'max:50'],
            'department_id' => ['required', 'exists:departments,id'],
            'shift_id'      => [
                'required',
                Rule::exists('shifts', 'id')->where('department_id', $request->department_id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'shift_id.exists' => 'Please select a shift that belongs to the chosen department.',
        ]);

        $teacher = new Teacher();
        // The device PIN — user-typed so it can match a PIN already enrolled
        // on a device (e.g. resolving an Unmatched Attendance / Pull Users
        // record) instead of always minting a brand new number.
        $teacher->teacher_no  = trim($request->teacher_no);
        $teacher->name        = $request->name;
        $teacher->email       = $request->email;
        $teacher->mobile      = $request->mobile;
        $teacher->designation = $request->designation;
        $teacher->department_id = $request->department_id;
        $teacher->shift_id      = $request->shift_id;

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
        $teacher     = Teacher::whereTeacherNo($teacher_no)->first();
        $route       = route('teachers.update', $teacher->id);
        $departments = \App\Models\Department::where('status', 'active')->orderBy('name')->get();
        $shifts      = \App\Models\Shift::where('status', 'active')->get();
        return view('teacher.teacher_add_edit', compact('teacher', 'route', 'departments', 'shifts'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'teacher_no' => [
                'required', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'email' => [
                'nullable', 'email', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'mobile' => [
                'nullable', 'string', 'max:50',
                Rule::unique('teachers')->whereNull('deleted_at')->ignore($id),
            ],
            'designation'   => ['nullable', 'max:50'],
            'department_id' => ['required', 'exists:departments,id'],
            'shift_id'      => [
                'required',
                Rule::exists('shifts', 'id')->where('department_id', $request->department_id),
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ], [
            'shift_id.exists' => 'Please select a shift that belongs to the chosen department.',
        ]);

        $teacher                = Teacher::findOrFail($id);
        $oldTeacherNo            = $teacher->teacher_no;
        $teacher->teacher_no    = trim($request->teacher_no);
        $teacher->name          = $request->name;
        $teacher->email         = $request->email;
        $teacher->mobile        = $request->mobile;
        $teacher->designation   = $request->designation;
        $teacher->department_id = $request->department_id;
        $teacher->shift_id      = $request->shift_id;

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
                        'name'          => $teacher->name,
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
