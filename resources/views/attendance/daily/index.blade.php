@extends('layouts.app')
@section('title', 'Daily Report')

@push('css')
<style>
    .dr-wrap { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 24px; max-width: 1100px; margin: 0 auto; }
    @media print {
        .navbar, .footer, .no-print, .background-animation { display: none !important; }
        .dr-wrap { border: none; padding: 0; }
        @page { size: A4 portrait; margin: 10mm; }
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap no-print">
        <h3 class="mb-2">Daily Report</h3>
        @if($report)
            <div class="mb-2 d-flex" style="gap:6px">
                <a href="{{ route('attendance.daily-report', array_filter(['institute_id' => isSuperAdmin() ? $institute : null, 'date' => $date, 'export' => 'pdf'])) }}"
                   target="_blank" class="btn btn-danger" {!! tooltip('Download as A4 PDF') !!}><i class="fas fa-file-pdf mr-1"></i> PDF</a>
                <button type="button" class="btn btn-dark" onclick="window.print()" {!! tooltip('Print') !!}><i class="fas fa-print"></i></button>
            </div>
        @endif
    </div>

    <div class="p-3 mb-3 no-print" style="background:#fff;border-radius:14px;border:1px solid #e5e7eb;">
        <form method="GET" action="{{ route('attendance.daily-report') }}" class="row align-items-end">
            <div class="col-lg-4 col-md-4 col-12">
                <div class="form-group mb-2">
                    <label class="form-label">Institute @if(isSuperAdmin()){!! starSign() !!}@endif</label>
                    @include('partials.institute_select', ['required' => true])
                    @error('institute_id') {!! displayError($message) !!} @enderror
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-12">
                <div class="form-group mb-2">
                    <label class="form-label">Date {!! starSign() !!}</label>
                    <input type="text" name="date" class="form-control flat_datepicker {{ hasError('date') }}" value="{{ $date }}"
                           autocomplete="off" placeholder="YYYY-MM-DD">
                    @error('date') {!! displayError($message) !!} @enderror
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-12">
                <div class="form-group mb-2 d-flex" style="gap:6px">
                    <button class="btn btn-primary flex-fill"><i class="fas fa-search mr-1"></i> Show</button>
                    <a href="{{ route('attendance.daily-report', array_filter(['institute_id' => isSuperAdmin() ? $institute : null])) }}"
                       class="btn btn-secondary" {!! tooltip('Today') !!}><i class="fas fa-undo"></i></a>
                </div>
            </div>
        </form>
    </div>

    @if(! $report)
        {{-- super admin: nothing until an institute is chosen --}}
        <div class="dr-wrap text-center py-5">
            <i class="fas fa-university fa-3x text-muted mb-3" style="opacity:.35"></i>
            <h5 class="text-muted mb-1">Select an institute</h5>
            <p class="text-muted mb-0">Choose an institute and a date, then click Show.</p>
        </div>
    @else
        <div class="dr-wrap">
            @include('attendance.daily._sheet', ['mode' => 'web'])
        </div>
    @endif
@endsection
