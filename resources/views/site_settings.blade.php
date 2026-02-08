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

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Update Site Settings</h3>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Site Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('update-site-settings') }}" id="prevent-form" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Site Name {!! starSign() !!}</label>
                            <input type="text" name="site_name"
                                   value="{{ old('site_name') ?? siteSettings()->site_name ?? '' }}"
                                   class="form-control {{ hasError('site_name') }}"
                                   placeholder="Site Name">
                            @error('site_name')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Email {!! starSign() !!}</label>
                            <input type="text" name="email" value="{{ old('email') ?? siteSettings()->email ?? '' }}"
                                   class="form-control {{ hasError('email') }}"
                                   placeholder="Email">
                            @error('email')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Mobile {!! starSign() !!}</label>
                            <input type="text" name="mobile" value="{{ old('mobile') ?? siteSettings()->mobile ?? '' }}"
                                   class="form-control {{ hasError('mobile') }}"
                                   placeholder="Mobile">
                            @error('mobile')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">In Time {!! starSign() !!}</label>
                            <input type="text" name="in_time"
                                   value="{{ old('in_time') ?? siteSettings()->in_time ?? '' }}"
                                   class="form-control {{ hasError('in_time') }} flat_timepicker"
                                   placeholder="In Time">
                            @error('in_time')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Out Time {!! starSign() !!}</label>
                            <input type="text" name="out_time"
                                   value="{{ old('out_time') ?? siteSettings()->out_time ?? '' }}"
                                   class="form-control {{ hasError('out_time') }} flat_timepicker"
                                   placeholder="Out">
                            @error('out_time')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="weekly_holidays" class="form-label">Weekly Holidays</label>
                            <select name="weekly_holidays[]" id="weekly_holidays" class="form-control select2" multiple
                                    data-placeholder="Weekly Holidays ...">
                                @foreach (weekDays() as $day)
                                    <option value="{{ $day }}"
                                        {{ !empty(weeklyHolidays()) && in_array($day, weeklyHolidays()) ? 'selected' : '' }}>
                                        {{ $day ?? '' }}</option>
                                @endforeach
                            </select>
                            @error('weekly_holidays')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">Office Holidays</label>
                            <!-- Calendar Input -->
                            <input type="text"
                                   id="holiday_picker"
                                   class="form-control flat_multi_datepicker"
                                   placeholder="Select dates">
                            <!-- Selected Dates -->
                            <div id="holiday_tags" class="mt-2"></div>
                            <!-- Actual form value -->
                            <input type="hidden"
                                   name="office_holidays"
                                   id="office_holidays"
                                   value='@json(officeHolidays())'>
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


