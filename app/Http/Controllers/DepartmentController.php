<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::with('shift')
            ->withCount('teachers')
            ->when($request->filled('shift_id'), fn ($q) => $q->where('shift_id', $request->shift_id))
            ->orderBy('name')
            ->paginate(20);

        $shifts = Shift::orderBy('in_time')->get(['id', 'title', 'in_time', 'out_time']);

        return view('department.department_list', compact('departments', 'shifts'));
    }

    public function create()
    {
        $route  = route('departments.store');
        $shifts = $this->shiftOptions();
        return view('department.department_add_edit', compact('route', 'shifts'));
    }

    public function store(Request $request)
    {
        Department::create($this->validated($request));

        return redirect()->route('departments.index')->with(successMessage());
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $route      = route('departments.update', $department->id);
        $shifts     = $this->shiftOptions($department->shift_id);
        return view('department.department_add_edit', compact('department', 'route', 'shifts'));
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        $department->update($this->validated($request, $department->id));

        return redirect()->route('departments.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        if ($department->teachers()->exists()) {
            return redirect()->route('departments.index')
                ->with(dangerMessage('danger', 'Cannot delete — teachers are still assigned to this department.'));
        }

        $department->delete();
        return redirect()->route('departments.index')->with(deleteMessage());
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id')->whereNull('deleted_at'),
            ],
            'status' => 'required|in:active,inactive',
        ], [
            'shift_id.required' => 'Please select the shift this department follows.',
        ]);

        return [
            'name'     => trim($request->name),
            'shift_id' => $request->shift_id,
            'status'   => $request->status,
        ];
    }

    /**
     * Active shifts, plus the currently assigned one on edit even if it has
     * since been made inactive (so the select never silently loses it).
     */
    protected function shiftOptions(?int $currentShiftId = null)
    {
        return Shift::where('status', 'active')
            ->when($currentShiftId, fn ($q) => $q->orWhere('id', $currentShiftId))
            ->orderBy('in_time')
            ->get(['id', 'title', 'in_time', 'out_time', 'status']);
    }
}
