<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount(['departments', 'teachers'])
            ->orderBy('in_time')
            ->paginate(20);

        return view('shift.shift_list', compact('shifts'));
    }

    public function create()
    {
        $route = route('shifts.store');
        return view('shift.shift_add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        Shift::create($this->validated($request));

        return redirect()->route('shifts.index')->with(successMessage());
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        $route = route('shifts.update', $shift->id);
        return view('shift.shift_add_edit', compact('shift', 'route'));
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);
        $shift->update($this->validated($request, $shift->id));

        return redirect()->route('shifts.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $shift = Shift::withCount('departments')->findOrFail($id);

        if ($shift->departments_count > 0) {
            return redirect()->route('shifts.index')
                ->with(dangerMessage('danger', 'Cannot delete — this shift is assigned to ' . $shift->departments_count . ' department(s). Move them to another shift first.'));
        }

        $shift->delete();
        return redirect()->route('shifts.index')->with(deleteMessage());
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $this->validate($request, [
            'title' => [
                'required', 'string', 'max:100',
                Rule::unique('shifts')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'in_time'              => 'required',
            'out_time'             => 'required',
            'late_count_time'      => 'nullable',
            'early_out_count_time' => 'nullable',
            'punch_before_minutes' => 'nullable|integer|min:0|max:720',
            'punch_after_minutes'  => 'nullable|integer|min:0|max:720',
            'status'               => 'required|in:active,inactive',
        ], [
            'punch_before_minutes.max' => 'Punch window before in time can be at most 12 hours (720 minutes).',
            'punch_after_minutes.max'  => 'Punch window after out time can be at most 12 hours (720 minutes).',
        ]);

        $in  = $this->normalizeTime($request->in_time, 'in_time');
        $out = $this->normalizeTime($request->out_time, 'out_time');

        // out <= in is allowed: it is a night shift ending the next day.
        if ($out === $in) {
            throw ValidationException::withMessages(['out_time' => 'Out time cannot be the same as in time.']);
        }

        $late  = $request->filled('late_count_time') ? $this->normalizeTime($request->late_count_time, 'late_count_time') : $in;
        $early = $request->filled('early_out_count_time') ? $this->normalizeTime($request->early_out_count_time, 'early_out_count_time') : $out;
        $before = $request->filled('punch_before_minutes') ? (int) $request->punch_before_minutes : Shift::DEFAULT_PUNCH_BEFORE_MINUTES;
        $after  = $request->filled('punch_after_minutes') ? (int) $request->punch_after_minutes : Shift::DEFAULT_PUNCH_AFTER_MINUTES;

        // All checks measured forward from in_time, so 00:10 counts as after 22:00.
        $length = Shift::durationMinutes($in, $out) * 60;
        $errors = [];
        if (Shift::secondsAfter($in, $late) >= $length) {
            $errors['late_count_time'] = 'Late count time must be between the in time and the out time.';
        }
        if (Shift::secondsAfter($in, $early) > $length || Shift::secondsAfter($in, $early) === 0) {
            $errors['early_out_count_time'] = 'Early out count time must be after the in time and not later than the out time.';
        }
        if ($before + Shift::durationMinutes($in, $out) + $after > 1440) {
            $errors['punch_after_minutes'] = 'Punch window (before + shift length + after) must fit in 24 hours, otherwise a punch could belong to two shift days.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'title'                => trim($request->title),
            'in_time'              => $in,
            'out_time'             => $out,
            'late_count_time'      => $late,
            'early_out_count_time' => $early,
            'punch_before_minutes' => $before,
            'punch_after_minutes'  => $after,
            'is_overnight'         => $out < $in,
            'status'               => $request->status,
        ];
    }

    /**
     * The time picker submits 12-hour format ("08:00:00 AM") but MySQL's TIME
     * column needs 24-hour ("08:00:00"). Carbon reads either.
     */
    protected function normalizeTime(?string $value, string $field): string
    {
        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => "Enter a valid time (e.g. 08:00 AM).",
            ]);
        }
    }
}
