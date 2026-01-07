@extends('layouts.app')
@section('title', 'Location Tracking')

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        #locationMap {
            height: 600px;
            width: 100%;
            border-radius: 8px;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Tracking for: {{ $employee->name ?? 'N/A' }}</h5>
                    <p class="card-subtitle text-muted mt-1">Date: {{ \Carbon\Carbon::parse($attendanceDate)->format('F j, Y') }}</p>
                </div>
                <div class="card-body">
                    @if($checkIn && $checkIn->latitude && $checkIn->longitude)
                        <div id="locationMap"></div>
                        {{-- Pass data as simple attributes instead of JSON --}}
                        <div id="location-data"
                             data-check-in-lat="{{ $checkIn->latitude }}"
                             data-check-in-lng="{{ $checkIn->longitude }}"
                             data-check-in-time="{{ $checkIn->punch_time }}"
                             @if($checkOut && $checkOut->latitude && $checkOut->longitude)
                                 data-check-out-lat="{{ $checkOut->latitude }}"
                                 data-check-out-lng="{{ $checkOut->longitude }}"
                                 data-check-out-time="{{ $checkOut->punch_time }}"
                             @endif
                             data-employee-name="{{ $employee->name ?? 'Employee' }}"
                             hidden>
                        </div>
                    @else
                        <div class="alert alert-warning text-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No attendance records with location data found for this employee on the selected date.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    {{-- <script src="https://cdn.jsdelivr.net/gh/pointhi/leaflet-color-markers/dist/leaflet-color-markers.js"></script> --}}
    <script src="{{ asset('assets/admin/js/custom/track_location.js') }}"></script>
@endpush
