<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Teacher;
use App\Services\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Configuration → Leave Entry: a teacher/staff member's leave for a date range.
 * Days = working days in the range (the institute's holidays are not counted),
 * calculated here — the number shown on the form is only a preview.
 */
class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date'   => ['nullable', 'date'],
        ]);
        $institute = selectedInstituteId();

        $leaves = Leave::with(['teacher:id,name,teacher_no,department_id,image', 'teacher.department:id,name', 'type:id,name,code', 'institute:id,name'])
            ->forInstitute($institute)
            ->when($request->filled('teacher_id'), fn ($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->filled('leave_type_id'), fn ($q) => $q->where('leave_type_id', $request->leave_type_id))
            // any leave touching the chosen period
            ->when($request->filled('from_date'), fn ($q) => $q->where('to_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->where('from_date', '<=', $request->to_date))
            ->orderByDesc('from_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $teachers = Teacher::forInstitute($institute)->orderByNo()->get(['id', 'name', 'teacher_no']);
        $types    = LeaveType::forInstitute($institute)->orderBy('name')->get(['id', 'name', 'code']);

        return view('leave.leave_list', compact('leaves', 'teachers', 'types'));
    }

    public function create()
    {
        return view('leave.leave_add_edit', $this->formData() + ['route' => route('leaves.store')]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Leave::create($data + ['created_by' => auth()->id()]);

        return redirect()->route('leaves.index')->with(successMessage());
    }

    public function edit($id)
    {
        $leave = Leave::findOrFail($id);
        return view('leave.leave_add_edit', $this->formData($leave) + ['leave' => $leave, 'route' => route('leaves.update', $leave->id)]);
    }

    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);
        $leave->update($this->validated($request, $leave));

        return redirect()->route('leaves.index')->with(infoMessage());
    }

    public function destroy($id)
    {
        Leave::findOrFail($id)->delete();
        return redirect()->route('leaves.index')->with(deleteMessage());
    }

    protected function formData(?Leave $leave = null): array
    {
        // admin: everyone (filtered to the chosen institute in the form); institute: its own
        $teachers = Teacher::orderByNo()->get(['id', 'name', 'teacher_no', 'institute_id']);
        $types    = LeaveType::where('status', 'active')
            ->when($leave, fn ($q) => $q->orWhere('id', $leave->leave_type_id)) // keep an inactive type on edit
            ->orderBy('name')->get(['id', 'name', 'code', 'institute_id', 'status']);
        $instituteIds = authInstituteId() ? [authInstituteId()] : instituteOptions()->pluck('id');

        return [
            'teachers' => $teachers,
            'types'    => $types,
            'holidays' => LeaveService::holidayData($instituteIds),
        ];
    }

    protected function validated(Request $request, ?Leave $leave = null): array
    {
        $institute = authInstituteId() ?? ($request->filled('institute_id') ? (int) $request->institute_id : null);

        $this->validate($request, [
            'institute_id'  => isSuperAdmin()
                ? ['required', Rule::exists('users', 'id')->where('role', 'institute')->whereNull('deleted_at')]
                : ['nullable'],
            // the teacher and the type must belong to the same institute
            'teacher_id'    => ['required', Rule::exists('teachers', 'id')->where('institute_id', $institute)->whereNull('deleted_at')],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('institute_id', $institute)->whereNull('deleted_at')],
            'from_date'     => ['required', 'date'],
            'to_date'       => ['required', 'date', 'after_or_equal:from_date'],
            'reason'        => ['nullable', 'string', 'max:500'],
        ], [
            'institute_id.required'  => 'Please select the institute.',
            'teacher_id.required'    => 'Please select the teacher/staff.',
            'teacher_id.exists'      => 'This teacher/staff does not belong to the selected institute.',
            'leave_type_id.required' => 'Please select the leave type.',
            'leave_type_id.exists'   => 'This leave type does not belong to the selected institute.',
            'to_date.after_or_equal' => 'The "To" date must be on or after the "From" date.',
        ]);

        $from = Carbon::parse($request->from_date)->toDateString();
        $to   = Carbon::parse($request->to_date)->toDateString();

        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 365) {
            throw ValidationException::withMessages(['to_date' => 'One leave can cover at most one year.']);
        }
        if ($other = LeaveService::overlapping((int) $request->teacher_id, $from, $to, $leave?->id)) {
            throw ValidationException::withMessages(['from_date' => sprintf(
                'This teacher/staff already has a leave from %s to %s that overlaps these dates.',
                Carbon::parse($other->from_date)->format('d M Y'), Carbon::parse($other->to_date)->format('d M Y')
            )]);
        }
        $days = LeaveService::countDays($institute, $from, $to);
        if ($days === 0) {
            throw ValidationException::withMessages(['from_date' => 'Every day in this range is a holiday of the institute, so there is no leave day to record.']);
        }

        return array_filter(['institute_id' => $institute]) + [
            'teacher_id'    => (int) $request->teacher_id,
            'leave_type_id' => (int) $request->leave_type_id,
            'from_date'     => $from,
            'to_date'       => $to,
            'days'          => $days,
            'reason'        => $request->filled('reason') ? trim($request->reason) : null,
        ];
    }
}
