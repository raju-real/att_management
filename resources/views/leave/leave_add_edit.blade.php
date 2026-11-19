@extends('layouts.app')
@section('title', 'Leave Add/Edit')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($leave) ? 'Edit' : 'Add' }} Leave</h3>
        <a href="{{ route('leaves.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plane-departure mr-2"></i> Leave Information</h5>
        </div>
        <div class="card-body">
            @if ($types->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    No leave type yet. <a href="{{ route('leave-types.create') }}" class="font-weight-bold">Create a leave type</a> first (e.g. Casual, Medical).
                </div>
            @endif

            @php
                $selTeacher = (string) (old('teacher_id') ?? ($leave->teacher_id ?? ''));
                $selType    = (string) (old('leave_type_id') ?? ($leave->leave_type_id ?? ''));
            @endphp
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($leave)
                    @method('PUT')
                @endisset

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Institute {!! starSign() !!}</label>
                            @include('partials.institute_select', ['required' => true, 'selected' => $leave->institute_id ?? null])
                            @error('institute_id') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Teacher/Staff {!! starSign() !!}</label>
                            <select name="teacher_id" id="leaveTeacher" class="form-control {{ hasError('teacher_id') }}" data-placeholder="-- Select Teacher/Staff --">
                                <option value="">-- Select Teacher/Staff --</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}" data-institute="{{ $t->institute_id }}" {{ $selTeacher === (string) $t->id ? 'selected' : '' }}>
                                        {{ $t->name }} ({{ $t->teacher_no }})
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Leave Type {!! starSign() !!}</label>
                            <select name="leave_type_id" id="leaveType" class="form-control {{ hasError('leave_type_id') }}">
                                <option value="">-- Select Leave Type --</option>
                                @foreach ($types as $lt)
                                    <option value="{{ $lt->id }}" data-institute="{{ $lt->institute_id }}" {{ $selType === (string) $lt->id ? 'selected' : '' }}>
                                        {{ $lt->name }}{{ $lt->code ? ' (' . $lt->code . ')' : '' }}{{ $lt->status !== 'active' ? ' [inactive]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('leave_type_id') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">From Date {!! starSign() !!}</label>
                            <input type="date" name="from_date" id="leaveFrom" class="form-control {{ hasError('from_date') }}" placeholder="From date"
                                   value="{{ old('from_date') ?? (isset($leave) ? $leave->from_date->toDateString() : today()->toDateString()) }}">
                            @error('from_date') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">To Date {!! starSign() !!}</label>
                            <input type="date" name="to_date" id="leaveTo" class="form-control {{ hasError('to_date') }}" placeholder="To date"
                                   value="{{ old('to_date') ?? (isset($leave) ? $leave->to_date->toDateString() : today()->toDateString()) }}">
                            @error('to_date') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Leave Days</label>
                            <input type="text" id="leaveDays" class="form-control" readonly placeholder="Counted from the dates" value="{{ $leave->days ?? '' }}">
                            <small class="text-muted" id="leaveDaysHint">Working days only: the institute's weekly and office holidays are not counted.</small>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" rows="2" maxlength="500" class="form-control {{ hasError('reason') }}" placeholder="e.g. Fever, family function (optional)">{{ old('reason') ?? ($leave->reason ?? '') }}</textarea>
                            @error('reason') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    const HOLIDAYS = @json($holidays);   // {institute_id: {weekly: ['friday'], office: ['2026-12-16']}}
    const DAY = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const inst    = $('#instituteSelect');
    const teacher = document.getElementById('leaveTeacher');
    const type    = document.getElementById('leaveType');
    const from    = document.getElementById('leaveFrom');
    const to      = document.getElementById('leaveTo');
    const days    = document.getElementById('leaveDays');
    const hint    = document.getElementById('leaveDaysHint');

    // keep every teacher option, show only the chosen institute's ones (select2 reads the live options)
    const allTeachers = Array.from(teacher.options).filter(o => o.value).map(o => o.cloneNode(true));
    const instituteId = () => String(inst.val() || '');

    function filterTeachers() {
        const id = instituteId(), keep = teacher.value;
        teacher.querySelectorAll('option[value]:not([value=""])').forEach(o => o.remove());
        allTeachers.filter(o => id !== '' && o.dataset.institute === id).forEach(o => teacher.appendChild(o.cloneNode(true)));
        teacher.value = Array.from(teacher.options).some(o => o.value === keep) ? keep : '';
        if (window.jQuery && jQuery.fn.select2) jQuery(teacher).trigger('change.select2');
    }
    function filterTypes() {
        const id = instituteId();
        Array.from(type.options).forEach(o => {
            if (!o.value) return;
            const show = id !== '' && o.dataset.institute === id;
            o.hidden = !show; o.disabled = !show;
            if (!show && o.selected) type.value = '';
        });
    }
    // live preview of the working days in the range (the server counts again on save)
    function countDays() {
        const h = HOLIDAYS[instituteId()] || { weekly: [], office: [] };
        if (!from.value || !to.value) { days.value = ''; return; }
        const a = new Date(from.value + 'T00:00:00'), b = new Date(to.value + 'T00:00:00');
        if (b < a) { days.value = ''; hint.textContent = 'The "To" date must be on or after the "From" date.'; hint.className = 'text-danger small'; return; }
        let n = 0, off = 0;
        for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
            const ymd = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            if (h.weekly.includes(DAY[d.getDay()]) || h.office.includes(ymd)) off++; else n++;
        }
        days.value = n + (n === 1 ? ' day' : ' days');
        hint.className = 'text-muted small';
        hint.textContent = off ? off + ' holiday' + (off > 1 ? 's' : '') + ' in this range not counted.' : 'Working days only: the institute\'s weekly and office holidays are not counted.';
    }

    if (window.jQuery && jQuery.fn.select2) {
        jQuery(teacher).select2({ width: '100%', placeholder: '-- Select Teacher/Staff --', allowClear: true });
    }
    inst.on('change', () => { filterTeachers(); filterTypes(); countDays(); });
    from.addEventListener('change', () => { if (to.value && to.value < from.value) to.value = from.value; countDays(); });
    to.addEventListener('change', countDays);
    filterTeachers(); filterTypes(); countDays();
})();
</script>
@endpush
