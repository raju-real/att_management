<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('teachers')->latest()->paginate(20);
        return view('department.department_list', compact('departments'));
    }

    public function create()
    {
        $route = route('departments.store');
        return view('department.department_add_edit', compact('route'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments')->whereNull('deleted_at'),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        Department::create([
            'name'   => $request->name,
            'status' => $request->status,
        ]);

        return redirect()->route('departments.index')->with(successMessage());
    }

    public function edit($id)
    {
        $department = Department::findOrFail($id);
        $route = route('departments.update', $department->id);
        return view('department.department_add_edit', compact('department', 'route'));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('departments')->whereNull('deleted_at')->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $department = Department::findOrFail($id);
        $department->update([
            'name'   => $request->name,
            'status' => $request->status,
        ]);

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
}
