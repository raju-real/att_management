<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Configuration → Leave Types: each institute's own types (Casual, Medical, ...). */
class LeaveTypeController extends Controller
{
    public function index()
    {
        $types = LeaveType::with('institute:id,name')
            ->withCount('leaves')
            ->withSum('leaves as days_taken', 'days')
            ->forInstitute(selectedInstituteId())
            ->orderBy('name')
            ->paginate(20);

        return view('leave_type.leave_type_list', compact('types'));
    }

    public function create()
    {
        return view('leave_type.leave_type_add_edit', ['route' => route('leave-types.store')]);
    }

    public function store(Request $request)
    {
        LeaveType::create($this->validated($request));
        return redirect()->route('leave-types.index')->with(successMessage());
    }

    public function edit($id)
    {
        $type  = LeaveType::findOrFail($id);
        $route = route('leave-types.update', $type->id);
        return view('leave_type.leave_type_add_edit', compact('type', 'route'));
    }

    public function update(Request $request, $id)
    {
        $type = LeaveType::findOrFail($id);
        $type->update($this->validated($request, $type->id));
        return redirect()->route('leave-types.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        $type = LeaveType::findOrFail($id);
        if ($type->leaves()->exists()) {
            return redirect()->route('leave-types.index')
                ->with(dangerMessage('danger', 'Cannot delete — leaves are recorded with this type. Make it inactive instead.'));
        }
        $type->delete();
        return redirect()->route('leave-types.index')->with(deleteMessage());
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        // institute: forced for institute users, chosen by the admin
        $institute = authInstituteId() ?? ($request->filled('institute_id') ? (int) $request->institute_id : null);

        $this->validate($request, [
            'institute_id'  => isSuperAdmin()
                ? ['required', Rule::exists('users', 'id')->where('role', 'institute')->whereNull('deleted_at')]
                : ['nullable'],
            'name'          => ['required', 'string', 'max:100',
                Rule::unique('leave_types')->where('institute_id', $institute)->whereNull('deleted_at')->ignore($ignoreId)],
            'code'          => ['nullable', 'string', 'max:10'],
            'days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            'status'        => ['required', 'in:active,inactive'],
        ], [
            'institute_id.required' => 'Please select the institute.',
            'name.unique'           => 'This institute already has a leave type with this name.',
        ]);

        return array_filter(['institute_id' => $institute]) + [
            'name'          => trim($request->name),
            'code'          => $request->filled('code') ? strtoupper(trim($request->code)) : null,
            'days_per_year' => $request->filled('days_per_year') ? (int) $request->days_per_year : null,
            'status'        => $request->status,
        ];
    }
}
