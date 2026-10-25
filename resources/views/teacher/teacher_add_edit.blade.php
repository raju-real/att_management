@extends('layouts.app')
@section('title', 'Teacher/Staff Add/Edit')

@push('css')
<style>
    /* ── Image Upload Zone ── */
    .teacher-img-zone {
        border: 2px dashed rgba(99, 102, 241, 0.5);
        border-radius: 16px;
        background: rgba(99, 102, 241, 0.04);
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    .teacher-img-zone:hover {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.09);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .teacher-img-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .img-preview-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 12px;
        border: 3px solid rgba(99, 102, 241, 0.4);
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(99,102,241,0.2);
        transition: border-color 0.3s;
    }
    .img-preview-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .img-preview-wrap .no-img-icon {
        font-size: 48px;
        color: #c4c9d4;
    }
    .img-upload-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .img-upload-label span {
        color: #6366f1;
        font-weight: 600;
    }
    .remove-img-btn {
        display: none;
        margin-top: 10px;
    }
    .img-preview-wrap.has-image {
        border-color: #6366f1;
    }
</style>
@endpush

@section('content')
    @php
        $rowsOf = function (string $field, array $columns) use (&$teacher) {
            $rows = old($field) ?? (isset($teacher) ? ($teacher->{$field} ?? []) : []);
            $rows = array_values((array) $rows);
            return $rows ?: [array_fill_keys(array_keys($columns), '')]; // always one blank row
        };
        $val = fn (string $f, $default = '') => old($f) ?? (isset($teacher) ? ($teacher->{$f} ?? $default) : $default);
        $dateVal = function (string $f) use (&$teacher) {
            if (old($f) !== null) return old($f);
            return isset($teacher) && $teacher->{$f} ? $teacher->{$f}->format('Y-m-d') : '';
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($teacher) ? 'Edit' : 'Add' }} Teacher/Staff</h3>
        <div>
            @isset($teacher)
                <a href="{{ route('teacher-profiles.show', $teacher->id) }}" class="btn btn-info text-white mr-1" {!! tooltip('View Profile') !!}>
                    <i class="fas fa-id-card mr-1"></i> Profile
                </a>
            @endisset
            <a href="{{ route('teachers.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ $route }}" id="prevent-form" method="POST" enctype="multipart/form-data">
        @csrf
        @isset($teacher)
            @method('PUT')
        @endisset

        {{-- ═══ Basic information ═══ --}}
        <div class="card admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Teacher/Staff Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- ── Image Upload Column ── --}}
                    <div class="col-md-3 mb-3">
                        <label class="form-label d-block">Photo</label>
                        <div class="teacher-img-zone" id="imgZone">
                            <input type="file" name="image" id="teacherImageInput"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <div class="img-preview-wrap" id="imgPreviewWrap">
                                @if(isset($teacher) && $teacher->image && file_exists($teacher->image))
                                    <img id="imgPreview" src="{{ asset($teacher->image) }}" alt="Photo">
                                @else
                                    <img id="imgPreview" src="" alt="Preview" style="display:none;">
                                    <i class="fas fa-user-circle no-img-icon" id="noImgIcon"></i>
                                @endif
                            </div>
                            <p class="img-upload-label mb-0">
                                <span><i class="fas fa-cloud-upload-alt"></i> Click to upload</span><br>
                                <small>JPG, PNG, GIF or WEBP &bull; Max 2MB</small>
                            </p>
                        </div>
                        @error('image')
                            {!! displayError($message) !!}
                        @enderror

                        {{-- Remove image option on edit --}}
                        @isset($teacher)
                            @if($teacher->image && file_exists($teacher->image))
                                <div class="mt-2 remove-img-btn" id="removeImgWrap" style="display:block!important;">
                                    <label class="d-flex align-items-center" style="cursor:pointer;font-size:13px;color:#ef4444;">
                                        <input type="checkbox" name="remove_image" value="1" id="removeImgCheck" class="mr-1">
                                        Remove current photo
                                    </label>
                                </div>
                            @endif
                        @endisset
                    </div>

                    {{-- ── Form Fields ── --}}
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Institute {!! starSign() !!}</label>
                                    @include('partials.institute_select', ['required' => true, 'selected' => $teacher->institute_id ?? request('institute_id')])
                                    @error('institute_id')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Department {!! starSign() !!}</label>
                                    <select name="department_id" id="departmentSelect" class="form-control {{ hasError('department_id') }}">
                                        <option value="">-- Select Department --</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}" data-institute="{{ $department->institute_id }}"
                                                data-shift="{{ $department->shift ? $department->shift->title . ' • In ' . timeFormat($department->shift->in_time, 'h:i A') . ' • Out ' . timeFormat($department->shift->out_time, 'h:i A') : '' }}"
                                                {{ (string) (old('department_id') ?? ($teacher->department_id ?? '')) === (string) $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}@if(isSuperAdmin() && $department->institute) ({{ $department->institute->name }})@endif</option>
                                        @endforeach
                                    </select>
                                    <small class="d-block mt-1" id="shiftTimesHint"></small>
                                    @if($departments->isEmpty())
                                        <small class="text-danger">No active departments yet. <a href="{{ route('departments.create') }}">Create one first</a>.</small>
                                    @endif
                                    @error('department_id')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Name {!! starSign() !!}</label>
                                    <input type="text" name="name"
                                        value="{{ old('name') ?? request('name') ?? ($teacher->name ?? '') }}"
                                        class="form-control {{ hasError('name') }}" placeholder="Full Name">
                                    @error('name')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">
                                        Device ID / Teacher-Staff No {!! starSign() !!}
                                    </label>
                                    <input type="text" name="teacher_no"
                                        value="{{ old('teacher_no') ?? request('pin') ?? ($teacher->teacher_no ?? \App\Models\Teacher::getTeacherNo()) }}"
                                        class="form-control {{ hasError('teacher_no') }}"
                                        placeholder="PIN used on the fingerprint device">
                                    <small class="text-muted">
                                        Unique inside the institute. Set this to match a PIN already enrolled on the
                                        institute's device so it links up instead of creating a duplicate.
                                    </small>
                                    @error('teacher_no')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Designation</label>
                                    <input type="text" name="designation" value="{{ $val('designation') }}"
                                        class="form-control {{ hasError('designation') }}" placeholder="e.g. Assistant Teacher / Office Assistant">
                                    @error('designation')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" name="mobile" value="{{ $val('mobile') }}"
                                        class="form-control {{ hasError('mobile') }}" placeholder="Mobile Number">
                                    @error('mobile')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="text" name="email" value="{{ $val('email') }}"
                                        class="form-control {{ hasError('email') }}" placeholder="Email Address">
                                    @error('email')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Personal information ═══ --}}
        <div class="card admin-card mt-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-user mr-2"></i> Personal Information <small class="text-muted">(shown on the profile)</small></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ $dateVal('date_of_birth') }}" class="form-control {{ hasError('date_of_birth') }}">
                            @error('date_of_birth') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-control {{ hasError('gender') }}">
                                <option value="">-- Select --</option>
                                @foreach (\App\Models\Teacher::GENDERS as $g)
                                    <option value="{{ $g }}" {{ $val('gender') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </select>
                            @error('gender') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-control {{ hasError('blood_group') }}">
                                <option value="">-- Select --</option>
                                @foreach (\App\Models\Teacher::BLOOD_GROUPS as $bg)
                                    <option value="{{ $bg }}" {{ $val('blood_group') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                            @error('blood_group') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Religion</label>
                            <input type="text" name="religion" value="{{ $val('religion') }}" list="religionList"
                                class="form-control {{ hasError('religion') }}">
                            <datalist id="religionList">
                                @foreach (\App\Models\Teacher::RELIGIONS as $r)
                                    <option value="{{ $r }}">
                                @endforeach
                            </datalist>
                            @error('religion') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Subject <small class="text-muted">(subject teacher of)</small></label>
                            <input type="text" name="subject" value="{{ $val('subject') }}" class="form-control {{ hasError('subject') }}"
                                placeholder="e.g. Mathematics">
                            @error('subject') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Present Address</label>
                            <textarea name="present_address" rows="2" class="form-control {{ hasError('present_address') }}">{{ $val('present_address') }}</textarea>
                            @error('present_address') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Permanent Address
                                <a href="javascript:void(0)" class="small ml-1" id="copyAddress">(same as present)</a></label>
                            <textarea name="permanent_address" rows="2" class="form-control {{ hasError('permanent_address') }}">{{ $val('permanent_address') }}</textarea>
                            @error('permanent_address') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Education qualification ═══ --}}
        @include('teacher._rows_table', [
            'title' => 'Education Qualification', 'icon' => 'fa-graduation-cap', 'field' => 'education',
            'columns' => \App\Models\Teacher::EDUCATION_COLUMNS,
            'rows' => $rowsOf('education', \App\Models\Teacher::EDUCATION_COLUMNS),
        ])

        {{-- ═══ Job information ═══ --}}
        <div class="card admin-card mt-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-briefcase mr-2"></i> Job Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Advertisement Newspaper &amp; Date</label>
                            <input type="text" name="newspaper_ad" value="{{ $val('newspaper_ad') }}" class="form-control {{ hasError('newspaper_ad') }}"
                                placeholder="e.g. Daily Ittefaq, 12-01-2015">
                            @error('newspaper_ad') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Joining Date of this Institution</label>
                            <input type="date" name="joining_date" value="{{ $dateVal('joining_date') }}" class="form-control {{ hasError('joining_date') }}">
                            @error('joining_date') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Joining Post</label>
                            <input type="text" name="joining_post" value="{{ $val('joining_post') }}" class="form-control {{ hasError('joining_post') }}">
                            @error('joining_post') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Time Scale</label>
                            <input type="text" name="time_scale" value="{{ $val('time_scale') }}" class="form-control {{ hasError('time_scale') }}">
                            @error('time_scale') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">M.P.O / Index Number</label>
                            <input type="text" name="mpo_index" value="{{ $val('mpo_index') }}" class="form-control {{ hasError('mpo_index') }}">
                            @error('mpo_index') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('teacher._rows_table', [
            'title' => 'Class Teacher', 'icon' => 'fa-chalkboard', 'field' => 'class_teacher',
            'columns' => \App\Models\Teacher::CLASS_COLUMNS,
            'rows' => $rowsOf('class_teacher', \App\Models\Teacher::CLASS_COLUMNS),
        ])
        @include('teacher._rows_table', [
            'title' => 'Subject Teacher', 'icon' => 'fa-book-open', 'field' => 'subject_teacher',
            'columns' => \App\Models\Teacher::CLASS_COLUMNS,
            'rows' => $rowsOf('subject_teacher', \App\Models\Teacher::CLASS_COLUMNS),
        ])

        <div class="text-right mt-3">
            <x-submit-button />
        </div>
    </form>
@endsection

@push('js')
<script>
// ── Repeatable rows (education / class teacher / subject teacher) ──
(function () {
    function renumber(table) {
        const field = table.dataset.field;
        table.querySelectorAll('tbody tr').forEach(function (tr, i) {
            tr.querySelectorAll('input[data-key]').forEach(function (inp) {
                inp.name = field + '[' + i + '][' + inp.dataset.key + ']';
            });
        });
    }
    document.querySelectorAll('.add-row-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const table = document.getElementById(btn.dataset.target);
            const first = table.querySelector('tbody tr');
            const row   = first.cloneNode(true);
            row.querySelectorAll('input').forEach(function (i) { i.value = ''; i.classList.remove('is-invalid'); });
            table.querySelector('tbody').appendChild(row);
            renumber(table);
        });
    });
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row-btn');
        if (!btn) return;
        const table = btn.closest('table');
        const rows  = table.querySelectorAll('tbody tr');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            rows[0].querySelectorAll('input').forEach(function (i) { i.value = ''; });
        }
        renumber(table);
    });

    const copy = document.getElementById('copyAddress');
    if (copy) {
        copy.addEventListener('click', function () {
            document.querySelector('[name="permanent_address"]').value = document.querySelector('[name="present_address"]').value;
        });
    }
})();

// ── Departments belong to an institute: only show the chosen institute's ones ──
(function () {
    const inst = $('#instituteSelect');
    const dept = document.getElementById('departmentSelect');
    if (!inst.length || !dept) return;

    function filterDepartments() {
        const id = String(inst.val() || '');
        Array.from(dept.options).forEach(function (opt) {
            if (!opt.value) return;
            const show = id !== '' && opt.dataset.institute === id;
            opt.hidden = !show;
            opt.disabled = !show;
            if (!show && opt.selected) { dept.value = ''; }
        });
        dept.dispatchEvent(new Event('change'));
    }
    inst.on('change', filterDepartments);
    filterDepartments();
})();

// ── Show the shift timing the chosen department follows ──
(function () {
    const deptSelect = document.getElementById('departmentSelect');
    const shiftHint  = document.getElementById('shiftTimesHint');
    if (!deptSelect) return;

    function updateHint() {
        const opt = deptSelect.options[deptSelect.selectedIndex];
        if (!opt || !opt.value) {
            shiftHint.className = 'd-block mt-1 text-muted';
            shiftHint.textContent = '';
        } else if (opt.dataset.shift) {
            shiftHint.className = 'd-block mt-1 text-info';
            shiftHint.innerHTML = '<i class="fas fa-business-time mr-1"></i>' + opt.dataset.shift;
        } else {
            shiftHint.className = 'd-block mt-1 text-danger';
            shiftHint.textContent = 'This department has no shift assigned, so late and early-out cannot be counted.';
        }
    }

    deptSelect.addEventListener('change', updateHint);
    updateHint();
})();

(function () {
    const input   = document.getElementById('teacherImageInput');
    const preview = document.getElementById('imgPreview');
    const noIcon  = document.getElementById('noImgIcon');
    const wrap    = document.getElementById('imgPreviewWrap');

    if (!input) return;

    // If we already have an image (edit mode) — mark the wrap
    if (preview && preview.src && preview.src !== window.location.href) {
        wrap.classList.add('has-image');
    }

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (noIcon) noIcon.style.display = 'none';
            wrap.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    });

    // Remove image checkbox — hide preview
    const removeCheck = document.getElementById('removeImgCheck');
    if (removeCheck) {
        removeCheck.addEventListener('change', function () {
            if (this.checked) {
                preview.src = '';
                preview.style.display = 'none';
                if (noIcon) noIcon.style.display = '';
                wrap.classList.remove('has-image');
            } else {
                // Restore original image
                preview.src = '{{ isset($teacher) && $teacher->image ? asset($teacher->image) : "" }}';
                preview.style.display = preview.src ? 'block' : 'none';
                if (noIcon) noIcon.style.display = preview.src ? 'none' : '';
                if (preview.src) wrap.classList.add('has-image');
            }
        });
    }
})();
</script>
@endpush
