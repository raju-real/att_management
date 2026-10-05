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
            'in_time'  => 'required',
            'out_time' => 'required',
            'status'   => 'required|in:active,inactive',
        ]);

        $in  = $this->normalizeTime($request->in_time, 'in_time');
        $out = $this->normalizeTime($request->out_time, 'out_time');

        if ($out <= $in) {
            throw ValidationException::withMessages([
                'out_time' => 'Out time must be later than in time.',
            ]);
        }

        return [
            'title'    => trim($request->title),
            'in_time'  => $in,
            'out_time' => $out,
            'status'   => $request->status,
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
