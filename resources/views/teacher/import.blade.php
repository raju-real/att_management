@extends('layouts.app')
@section('title', 'Import Teachers/Staff')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Import Teachers/Staff</h3>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    @if(session('import_skipped'))
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i> Skipped rows</strong>
            <ul class="mb-0 mt-1 small">
                @foreach(session('import_skipped') as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-file-excel mr-2"></i> Upload Teachers/Staff File</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <a href="{{ route('teachers.import.demo') }}" class="btn btn-info btn-sm float-right text-white ml-2">
                    <i class="fas fa-download mr-1"></i> Demo CSV
                </a>
                <strong>Header columns:</strong>
                <code>teacher_no</code>, <code>name</code>, <code>email</code>, <code>mobile</code>, <code>designation</code>
                and optionally <code>department</code> (department name, exactly as in the Department list).
                <ul class="mb-0 mt-2 small">
                    <li><code>teacher_no</code> is the device PIN and is required. If a teacher with that number already exists, the row
                        <strong>updates</strong> them; otherwise it <strong>creates</strong> a new teacher.</li>
                    <li><code>name</code> is required for new teachers. Blank cells never erase existing data on update.</li>
                    <li>New teachers without a <code>department</code> value get the <strong>Default Department</strong> below.
                        Existing teachers keep their department unless the row gives one.</li>
                </ul>
            </div>

            <form action="{{ route('teachers.upload') }}" method="POST" enctype="multipart/form-data" id="prevent-form">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">Institute {!! starSign() !!}</label>
                            @include('partials.institute_select', ['required' => true, 'selected' => null])
                            @error('institute_id')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Select File (XLS, XLSX, CSV) {!! starSign() !!}</label>
                            <input type="file" name="file"
                                class="form-control-file @error('file') is-invalid @enderror"
                                accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Default Department <small class="text-muted">(for new teachers)</small></label>
                            <select name="department_id" id="departmentSelect" class="form-control {{ hasError('department_id') }}">
                                <option value="">-- None (use department column) --</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" data-institute="{{ $d->institute_id }}" {{ (string) old('department_id') === (string) $d->id ? 'selected' : '' }}>
                                        {{ $d->name }}@if($d->shift) ({{ $d->shift->title }}: {{ timeFormat($d->shift->in_time, 'h:i A') }} - {{ timeFormat($d->shift->out_time, 'h:i A') }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @if($departments->isEmpty())
                                <small class="text-danger">No active departments yet. <a href="{{ route('departments.create') }}">Create one first</a>.</small>
                            @endif
                            @error('department_id')
                                {!! displayError($message) !!}
                            @enderror
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
// Default department: only the chosen institute's departments.
(function () {
    const inst = $('#instituteSelect');
    const dept = document.getElementById('departmentSelect');
    if (!inst.length || !dept) return;
    function filterDepartments() {
        const id = String(inst.val() || '');
        Array.from(dept.options).forEach(function (opt) {
            if (!opt.value) return;
            const show = id !== '' && opt.dataset.institute === id;
            opt.hidden = !show; opt.disabled = !show;
            if (!show && opt.selected) dept.value = '';
        });
    }
    inst.on('change', filterDepartments);
    filterDepartments();
})();
</script>
@endpush
