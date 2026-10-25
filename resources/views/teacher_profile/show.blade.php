@extends('layouts.app')
@section('title', 'Teacher/Staff Profile - ' . $teacher->name)

@push('css')
<style>
    .tp-wrap { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px; max-width: 1000px; margin: 0 auto; }
    @media print {
        .navbar, .footer, .no-print, .background-animation { display: none !important; }
        .tp-wrap { border: none; padding: 0; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap no-print">
        <h3 class="mb-2">Teacher/Staff Profile</h3>
        <div class="mb-2 d-flex align-items-center flex-wrap" style="gap:6px">
            <form method="GET" action="{{ route('teacher-profiles.show', $teacher->id) }}" class="d-flex align-items-center" style="gap:6px">
                <label class="mb-0 text-muted small">Attendance year</label>
                <select name="year" class="form-control form-control-sm" style="width:100px" onchange="this.form.submit()">
                    @for($y = now()->year; $y >= now()->year - 6; $y--)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
            <a href="{{ route('teacher-profiles.show', [$teacher->id, 'year' => $year, 'export' => 'pdf']) }}" target="_blank"
               class="btn btn-danger" {!! tooltip('Download / print as A4 PDF') !!}><i class="fas fa-file-pdf mr-1"></i> PDF</a>
            <a href="{{ route('teachers.edit', $teacher->id) }}" class="btn btn-info text-white" {!! tooltip('Edit profile information') !!}>
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            <a href="{{ route('teacher-profiles.index', array_filter(['institute_id' => isSuperAdmin() ? $teacher->institute_id : null])) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <div class="tp-wrap">
        @include('teacher_profile._sheet', ['mode' => 'web'])
    </div>
@endsection
