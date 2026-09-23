<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::withCount('teachers')->with('department')->latest()->paginate(20);
        return view('shift.shift_list', compact('shifts'));
    }

    public function create()
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $route = route('shifts.store');
        return view('shift.shift_add_edit', compact('route', 'departments'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'department_id' => 'required|exists:departments,id',
            'title' => [
                'required', 'string', 'max:100',
                Rule::unique('shifts')->whereNull('deleted_at'),
            ],
            'in_time'  => 'required',
            'out_time' => 'required',
            'status'   => 'required|in:active,inactive',
        ]);

        Shift::create([
            'department_id' => $request->department_id,
            'title'         => $request->title,
            'in_time'       => $this->normalizeTime($request->in_time, 'in_time'),
            'out_time'      => $this->normalizeTime($request->out_time, 'out_time'),
            'status'        => $request->status,
        ]);

        return redirect()->route('shifts.index')->with(successMessage());
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $route = route('shifts.update', $shift->id);
        return view('shift.shift_add_edit', compact('shift', 'route', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'department_id' => 'required|exists:departments,id',
            'title' => [
                'required', 'string', 'max:100',
                Rule::unique('shifts')->whereNull('deleted_at')->ignore($id),
            ],
            'in_time'  => 'required',
            'out_time' => 'required',
            'status'   => 'required|in:active,inactive',
        ]);

        $shift = Shift::findOrFail($id);
        $shift->update([
            'department_id' => $request->department_id,
            'title'         => $request->title,
            'in_time'       => $this->normalizeTime($request->in_time, 'in_time'),
            'out_time'      => $this->normalizeTime($request->out_time, 'out_time'),
            'status'        => $request->status,
        ]);

        return redirect()->route('shifts.index')->with(infoMessage());
    }

    /**
     * The time picker widget submits 12-hour format ("12:00:00 AM"), but
     * MySQL's TIME column only accepts 24-hour format ("00:00:00") — that
     * mismatch was throwing SQLSTATE[22007] on every save. Carbon::parse()
     * reads either format, so we normalize here regardless of what the
     * widget (or a future different one) actually sends.
     */
    protected function normalizeTime(?string $value, string $field): string
    {
        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => "Enter a valid time for {$field} (e.g. 08:00 AM).",
            ]);
        }
    }

    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);

        if ($shift->teachers()->exists()) {
            return redirect()->route('shifts.index')
                ->with(dangerMessage('danger', 'Cannot delete — teachers are still assigned to this shift.'));
        }

        $shift->delete();
        return redirect()->route('shifts.index')->with(deleteMessage());
    }

    /**
     * AJAX: shifts belonging to a department, for the Teacher add/edit form's
     * department -> shift cascading select.
     */
    public function byDepartment($departmentId)
    {
        $shifts = Shift::where('department_id', $departmentId)
            ->where('status', 'active')
            ->orderBy('title')
            ->get(['id', 'title', 'in_time', 'out_time']);

        return response()->json($shifts);
    }
}
