<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Services\DevicePushService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Push / remove teachers or students on fingerprint devices from the
 * teacher & student lists (single row, selected rows, or everyone),
 * to all active devices or one chosen device.
 */
class DevicePushController extends Controller
{
    public function __construct(protected DevicePushService $push)
    {
    }

    /** POST /device-push/{group}  (group = teacher|student) */
    public function push(Request $request, string $group)
    {
        [$device, $people] = $this->input($request, $group);

        $r = $this->push->push($group, $people, $device);
        if ($r['devices'] === 0) {
            return back()->with(dangerMessage('danger', "No active device serves {$group}s. Check the device list (status and \"Device For\")."));
        }

        $head = count($people) . " {$group}(s) checked on {$r['devices']} device(s): {$r['added']} new, {$r['renamed']} name update(s), {$r['skipped']} already on device.";
        return back()->with(successMessage('success', $head . "\n" . implode("\n", $r['lines'])));
    }

    /** POST /device-remove/{group} */
    public function remove(Request $request, string $group)
    {
        [$device, $people] = $this->input($request, $group);

        $r = $this->push->remove($group, array_keys($people), $device);
        if ($r['devices'] === 0) {
            return back()->with(dangerMessage('danger', "No active device serves {$group}s."));
        }

        return back()->with(warningMessage('warning', count($people) . " {$group}(s) removed from {$r['devices']} device(s).\n" . implode("\n", $r['lines'])));
    }

    /**
     * Validated device id (null = all active devices) and the people as pin => name.
     *
     * @return array{0: ?int, 1: array<string, string>}
     */
    protected function input(Request $request, string $group): array
    {
        abort_unless(in_array($group, ['teacher', 'student'], true), 404);

        $request->validate([
            'device_id' => ['nullable', Rule::exists('devices', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'scope'     => ['required', 'in:selected,all'],
            'ids'       => ['required_if:scope,selected', 'array'],
            'ids.*'     => ['integer'],
        ], [
            'ids.required_if'  => 'Select at least one ' . $group . ' first.',
            'device_id.exists' => 'The chosen device is not active.',
        ]);

        $ids = $request->scope === 'all' ? null : array_map('intval', $request->ids ?? []);

        if ($group === 'teacher') {
            $people = Teacher::query()
                ->when($ids, fn ($q) => $q->whereIn('id', $ids))
                ->whereNotNull('teacher_no')
                ->get(['teacher_no', 'name'])
                ->mapWithKeys(fn ($t) => [(string) $t->teacher_no => $t->name ?: 'Teacher'])
                ->all();
        } else {
            $people = Student::query()
                ->when($ids, fn ($q) => $q->whereIn('id', $ids))
                ->whereNotNull('student_no')->where('student_no', '<>', '')
                ->get(['student_no', 'firstname', 'middlename', 'lastname'])
                ->mapWithKeys(fn ($s) => [(string) $s->student_no => showStudentFullName($s->firstname, $s->middlename, $s->lastname) ?: 'Student'])
                ->all();
        }

        if (! $people) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ids' => "No {$group}s with a device ID were found."]);
        }

        return [$request->filled('device_id') ? (int) $request->device_id : null, $people];
    }
}
