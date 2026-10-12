@extends('layouts.app')
@section('title', 'Month & Day Wise Summary')

@push('css')
    <style>
        .ms-wrap { overflow-x: auto; background: #fff; border-radius: 12px; padding: 16px; border: 1px solid #e5e7eb; }
        .ms-wrap .ms-table td, .ms-wrap .ms-table th { white-space: nowrap; }
        .ms-section + .ms-section { margin-top: 28px; padding-top: 20px; border-top: 2px dashed #e5e7eb; }
        @media print {
            .navbar, .footer, .no-print, .background-animation { display: none !important; }
            .ms-wrap { border: none; padding: 0; overflow: visible; }
            .ms-section + .ms-section { page-break-before: always; border: none; }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
@endpush

@php
    $years = range(\Carbon\Carbon::today()->year + 1, \Carbon\Carbon::today()->year - 5);
    $query = request()->only(['month', 'year', 'department_id', 'shift_id']);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap no-print">
        <h3 class="mb-2">Month &amp; Day Wise Summary</h3>
        <div class="mb-2 d-flex" style="gap:6px">
            <a href="{{ route('attendance.month-sheet', array_merge($query, ['month' => $sheet['month'], 'year' => $sheet['year'], 'export' => 'pdf'])) }}"
               class="btn btn-danger" {!! tooltip('Download as PDF (A4 landscape, one page per shift)') !!}>
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
            <a href="{{ route('attendance.month-sheet', array_merge($query, ['month' => $sheet['month'], 'year' => $sheet['year'], 'export' => 'xlsx'])) }}"
               class="btn btn-success" {!! tooltip('Download as Excel (.xlsx)') !!}>
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <button type="button" class="btn btn-dark" onclick="window.print()" {!! tooltip('Print') !!}><i class="fas fa-print"></i></button>
        </div>
    </div>

    <div class="rpt-filter p-3 mb-3 no-print" style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;">
        <form method="GET" action="{{ route('attendance.month-sheet') }}" class="row align-items-end">
            <div class="col-lg-2 col-md-3 col-6">
                <div class="form-group mb-2">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-control">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $sheet['month'] === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <div class="form-group mb-2">
                    <label class="form-label">Year</label>
                    <select name="year" class="form-control">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $sheet['year'] === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <div class="form-group mb-2">
                    <label class="form-label">Department <small class="text-muted">(optional)</small></label>
                    <select name="department_id" class="form-control">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ (string) request('department_id') === (string) $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-6">
                <div class="form-group mb-2">
                    <label class="form-label">Shift <small class="text-muted">(optional)</small></label>
                    <select name="shift_id" class="form-control">
                        <option value="">All Shifts</option>
                        @foreach($shifts as $sh)
                            <option value="{{ $sh->id }}" {{ (string) request('shift_id') === (string) $sh->id ? 'selected' : '' }}>
                                {{ $sh->title }} ({{ \App\Services\AttendanceReportService::shiftClock($sh->in_time) }} - {{ \App\Services\AttendanceReportService::shiftClock($sh->out_time, $sh->in_time) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-12 col-12">
                <div class="form-group mb-2 d-flex" style="gap:6px">
                    <button class="btn btn-primary flex-fill"><i class="fas fa-search mr-1"></i> Show</button>
                    <a href="{{ route('attendance.month-sheet') }}" class="btn btn-secondary" {!! tooltip('Current month') !!}><i class="fas fa-undo"></i></a>
                </div>
            </div>
        </form>
    </div>

    <div class="ms-wrap">
        @forelse($sheet['sections'] as $section)
            <div class="ms-section">
                @include('attendance.sheet._section', ['sheet' => $sheet, 'section' => $section, 'mode' => 'web'])
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-2x d-block mb-2" style="opacity:.3"></i>
                No teachers found for the selected filters.
            </div>
        @endforelse
    </div>
@endsection
