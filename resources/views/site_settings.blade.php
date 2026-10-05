@extends('layouts.app')
@section('title','Update Site Settings')

@push('css')
    <style>
        .holiday-chip {
            display: inline-flex;
            align-items: center;
            background: #e9f2ff;
            color: #0d6efd;
            padding: 5px 10px;
            border-radius: 20px;
            margin: 4px;
            font-size: 13px;
        }

        .holiday-chip .remove-date {
            cursor: pointer;
            margin-left: 8px;
            font-weight: bold;
        }
    </style>
@endpush

@php
    $siteIn  = old('in_time')  ?? (!empty(siteSettings()->in_time)  ? timeFormat(siteSettings()->in_time, 'H:i')  : '');
    $siteOut = old('out_time') ?? (!empty(siteSettings()->out_time) ? timeFormat(siteSettings()->out_time, 'H:i') : '');
    $selectedWeekly = old('weekly_holidays') ?? weeklyHolidays();
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Update Site Settings</h3>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-sliders-h mr-2"></i> Site Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('update-site-settings') }}" id="prevent-form" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Site / School Name {!! starSign() !!}</label>
                            <input type="text" name="site_name" maxlength="100"
                                   value="{{ old('site_name') ?? siteSettings()->site_name ?? '' }}"
                                   class="form-control {{ hasError('site_name') }}"
                                   placeholder="e.g. Demo High School">
                            <small class="text-muted">Shown in the menu bar, reports and the attendance board.</small>
                            @error('site_name')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Email <small class="text-muted">(optional)</small></label>
                            <input type="email" name="email" maxlength="100" value="{{ old('email') ?? siteSettings()->email ?? '' }}"
                                   class="form-control {{ hasError('email') }}"
                                   placeholder="office@school.edu">
                            @error('email')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Mobile <small class="text-muted">(optional)</small></label>
                            <input type="text" name="mobile" maxlength="20" value="{{ old('mobile') ?? siteSettings()->mobile ?? '' }}"
                                   class="form-control {{ hasError('mobile') }}"
                                   placeholder="01XXXXXXXXX">
                            @error('mobile')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-info py-2 mb-3 small">
                            <i class="fas fa-info-circle mr-1"></i>
                            Teachers are counted <strong>late in / early out</strong> against their department's
                            <a href="{{ route('shifts.index') }}">Shift</a>. The default times below apply only to
                            <strong>students</strong> and to teachers whose department has no shift. Leave them empty to skip
                            late/early checks for those.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Default In Time
                                <i class="fas fa-question-circle text-muted" {!! tooltip('Arrivals after this time count as Late In (students, and teachers without a shift)') !!}></i>
                            </label>
                            <input type="time" name="in_time" value="{{ $siteIn }}" class="form-control {{ hasError('in_time') }}">
                            @error('in_time')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Default Out Time
                                <i class="fas fa-question-circle text-muted" {!! tooltip('Leaving before this time counts as Early Out') !!}></i>
                            </label>
                            <input type="time" name="out_time" value="{{ $siteOut }}" class="form-control {{ hasError('out_time') }}">
                            @error('out_time')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="weekly_holidays" class="form-label">Weekly Holidays
                                <i class="fas fa-question-circle text-muted" {!! tooltip('Excluded from working days and absent counts in the monthly summary') !!}></i>
                            </label>
                            <select name="weekly_holidays[]" id="weekly_holidays" class="form-control select2" multiple
                                    data-placeholder="Select weekly holidays">
                                @foreach (weekDays() as $day)
                                    <option value="{{ $day }}" {{ in_array($day, (array) $selectedWeekly, true) ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                            @error('weekly_holidays')
                            {!! displayError($message) !!}
                            @enderror
                            @error('weekly_holidays.*')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">Office Holidays
                                <small class="text-muted">(pick dates one at a time; click &times; on a date to remove it)</small>
                            </label>
                            <input type="text" id="holiday_picker" class="form-control" placeholder="Click to add a holiday date" readonly>
                            <div id="holiday_tags" class="mt-2"></div>
                            <input type="hidden" name="office_holidays" id="office_holidays"
                                   value="{{ old('office_holidays') ?? json_encode(array_values(officeHolidays())) }}">
                            @error('office_holidays_list')
                            {!! displayError($message) !!}
                            @enderror
                            @error('office_holidays_list.*')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button/>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/site_settings.js') }}"></script>
@endpush
