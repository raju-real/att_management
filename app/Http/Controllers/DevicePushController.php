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
 *
 * Multi-institute: people are grouped by institute and each group only goes
 * to its own institute's devices — even when the super admin pushes "all".
 */
class DevicePushController extends Controller
{
    public function __construct(protected DevicePushService $push)
    {
    }

    /** POST /device-push/{group}  (group = teacher|student) */
    public function push(Request $request, string $group)
    {
        [$device, $groups] = $this->input($request, $group);
        $label = $group === 'teacher' ? 'teacher/staff' : 'student';

        $total = ['devices' => 0, 'added' => 0, 'renamed' => 0, 'skipped' => 0, 'lines' => [], 'people' => 0];
        foreach ($groups as $instituteId => $people) {
            $r = $this->push->push($group, $people, $device, $instituteId ?: null);
            foreach (['devices', 'added', 'renamed', 'skipped'] as $k) {
                $total[$k] += $r[$k];
            }
            $total['lines']   = array_merge($total['lines'], $r['lines']);
            $total['people'] += count($people);
        }

        if ($total['devices'] === 0) {
            return back()->with(dangerMessage('danger', "No active device of this institute serves {$label}s. Check the device list (status, institute and \"Device For\")."));
        }

        $head = "{$total['people']} {$label}(s) checked on {$total['devices']} device(s): {$total['added']} new, {$total['renamed']} name update(s), {$total['skipped']} already on device.";
        return back()->with(successMessage('success', $head . "\n" . implode("\n", $total['lines'])));
    }

    /** POST /device-remove/{group} */
    public function remove(Request $request, string $group)
    {
        [$device, $groups] = $this->input($request, $group);
        $label = $group === 'teacher' ? 'teacher/staff' : 'student';

        $devices = 0; $lines = []; $people = 0;
        foreach ($groups as $instituteId => $list) {
            $r = $this->push->remove($group, array_keys($list), $device, $instituteId ?: null);
            $devices += $r['devices'];
            $lines    = array_merge($lines, $r['lines']);
            $people  += count($list);
        }

        if ($devices === 0) {
            return back()->with(dangerMessage('danger', "No active device of this institute serves {$label}s."));
        }

        return back()->with(warningMessage('warning', "{$people} {$label}(s) removed from {$devices} device(s).\n" . implode("\n", $lines)));
    }

    /**
     * Validated device id (null = all active devices) and the people grouped
     * by institute: [institute_id => [pin => name]].
     *
     * @return array{0: ?int, 1: array<int, array<string, string>>}
     */
    protected function input(Request $request, string $group): array
    {
        abort_unless(in_array($group, ['teacher', 'student'], true), 404);

        $request->validate([
            // institute users may only pick their own devices (scoped by authInstituteId)
            'device_id' => ['nullable', Rule::exists('devices', 'id')->where('status', 'active')->whereNull('deleted_at')
                ->when(authInstituteId(), fn ($r) => $r->where('institute_id', authInstituteId()))],
            'scope'     => ['required', 'in:selected,all'],
            'ids'       => ['required_if:scope,selected', 'array'],
            'ids.*'     => ['integer'],
            'institute_id' => ['nullable', 'integer'],
        ], [
            'ids.required_if'  => 'Select at least one ' . ($group === 'teacher' ? 'teacher/staff' : 'student') . ' first.',
            'device_id.exists' => 'The chosen device is not active.',
        ]);

        $ids = $request->scope === 'all' ? null : array_map('intval', $request->ids ?? []);
        $institute = selectedInstituteId(); // forced for institute users, optional filter for the admin

        $groups = [];
        if ($group === 'teacher') {
            Teacher::query()
                ->when($ids, fn ($q) => $q->whereIn('id', $ids))
                ->when($institute, fn ($q) => $q->where('institute_id', $institute))
                ->whereNotNull('teacher_no')
                ->get(['teacher_no', 'name', 'institute_id'])
                ->each(function ($t) use (&$groups) {
                    $groups[(int) $t->institute_id][(string) $t->teacher_no] = $t->name ?: 'Teacher';
                });
        } else {
            Student::query()
                ->when($ids, fn ($q) => $q->whereIn('id', $ids))
                ->when($institute, fn ($q) => $q->where('institute_id', $institute))
                ->whereNotNull('student_no')->where('student_no', '<>', '')
                ->get(['student_no', 'firstname', 'middlename', 'lastname', 'institute_id'])
                ->each(function ($s) use (&$groups) {
                    $groups[(int) $s->institute_id][(string) $s->student_no] = showStudentFullName($s->firstname, $s->middlename, $s->lastname) ?: 'Student';
                });
        }

        // people without an institute have no devices to go to
        unset($groups[0]);

        if (! $groups) {
            throw \Illuminate\Validation\ValidationException::withMessages(['ids' => 'No ' . ($group === 'teacher' ? 'teachers/staff' : 'students') . ' with a device ID (and an institute) were found.']);
        }

        return [$request->filled('device_id') ? (int) $request->device_id : null, $groups];
    }
}
