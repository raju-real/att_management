@extends('layouts.app')
@section('title', 'Shift Add/Edit')

@php
    $t = fn ($field, $fallback = null) => old($field) ?? (isset($shift) && $shift->{$field} ? timeFormat($shift->{$field}, 'h:i:s A') : $fallback);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($shift) ? 'Edit' : 'Add' }} Shift</h3>
        <a href="{{ route('shifts.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Shift Information</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small">
                <i class="fas fa-info-circle mr-1"></i>
                A shift sets the standard <strong>In</strong> and <strong>Out</strong> time. Assign it to departments; their teachers use it.
                For a <strong>night shift</strong> enter an out time earlier than the in time (e.g. 10:00 PM → 06:00 AM): the out time is
                on the <strong>next day</strong> and all its punches are reported on the day the shift started.
            </div>
            <form action="{{ $route }}" id="prevent-form" method="POST">
                @csrf
                @isset($shift)
                    @method('PUT')
                @endisset

                <h6 class="text-muted text-uppercase small font-weight-bold mb-2">Standard times</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Title {!! starSign() !!}</label>
                            <input type="text" name="title" value="{{ old('title') ?? ($shift->title ?? '') }}"
                                class="form-control {{ hasError('title') }}" placeholder="e.g. Morning / Night Shift">
                            @error('title')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">In Time {!! starSign() !!}</label>
                            <input type="text" name="in_time" id="shIn" value="{{ $t('in_time') }}"
                                class="form-control {{ hasError('in_time') }} flat_timepicker" placeholder="08:00 AM">
                            @error('in_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Out Time {!! starSign() !!}
                                <i class="fas fa-question-circle text-muted" {!! tooltip('Earlier than the in time = night shift ending the next day') !!}></i></label>
                            <input type="text" name="out_time" id="shOut" value="{{ $t('out_time') }}"
                                class="form-control {{ hasError('out_time') }} flat_timepicker" placeholder="02:00 PM">
                            @error('out_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}"
                                        {{ (old('status') ?? ($shift->status ?? 'active')) === $status->value ? 'selected' : '' }}>
                                        {{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                <h6 class="text-muted text-uppercase small font-weight-bold mb-2 mt-2">Late / early-out rules</h6>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Late counts after
                                <i class="fas fa-question-circle text-muted" {!! tooltip('First punch after this time = Late In. Empty = the in time (no grace). Late minutes are counted from the in time.') !!}></i></label>
                            <input type="text" name="late_count_time" id="shLate" value="{{ $t('late_count_time') }}"
                                class="form-control {{ hasError('late_count_time') }} flat_timepicker" placeholder="Same as in time">
                            @error('late_count_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Early out counts before
                                <i class="fas fa-question-circle text-muted" {!! tooltip('Last punch before this time = Early Out. Empty = the out time.') !!}></i></label>
                            <input type="text" name="early_out_count_time" id="shEarly" value="{{ $t('early_out_count_time') }}"
                                class="form-control {{ hasError('early_out_count_time') }} flat_timepicker" placeholder="Same as out time">
                            @error('early_out_count_time')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Punches accepted before in (min)
                                <i class="fas fa-question-circle text-muted" {!! tooltip('How early a punch still belongs to this shift. Default 180 (3 h).') !!}></i></label>
                            <input type="number" min="0" max="720" name="punch_before_minutes" id="shBefore"
                                value="{{ old('punch_before_minutes') ?? ($shift->punch_before_minutes ?? \App\Models\Shift::DEFAULT_PUNCH_BEFORE_MINUTES) }}"
                                class="form-control {{ hasError('punch_before_minutes') }}">
                            @error('punch_before_minutes')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Punches accepted after out (min)
                                <i class="fas fa-question-circle text-muted" {!! tooltip('How late an out punch still belongs to this shift. Default 360 (6 h).') !!}></i></label>
                            <input type="number" min="0" max="720" name="punch_after_minutes" id="shAfter"
                                value="{{ old('punch_after_minutes') ?? ($shift->punch_after_minutes ?? \App\Models\Shift::DEFAULT_PUNCH_AFTER_MINUTES) }}"
                                class="form-control {{ hasError('punch_after_minutes') }}">
                            @error('punch_after_minutes')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border small mb-0" id="shPreview" style="display:none"></div>

                <div class="text-right mt-3">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    const $ = id => document.getElementById(id);
    // Accepts "08:00:00 AM", "08:00 PM" or "20:00"; returns minutes after midnight or null.
    function mins(v) {
        if (!v) return null;
        const m = String(v).trim().match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*([AP]M)?$/i);
        if (!m) return null;
        let h = +m[1];
        if (m[3]) { h = h % 12; if (m[3].toUpperCase() === 'PM') h += 12; }
        return h * 60 + (+m[2]);
    }
    function fmt(t) {
        t = ((t % 1440) + 1440) % 1440;
        const h = Math.floor(t / 60), m = t % 60, ap = h >= 12 ? 'PM' : 'AM';
        return String((h % 12) || 12).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ' ' + ap;
    }
    const day = (base, t) => (t - base >= 1440 ? ' (+1 day)' : (t < base ? ' (previous day)' : ''));

    function render() {
        const inn = mins($('shIn').value), out = mins($('shOut').value);
        const box = $('shPreview');
        if (inn === null || out === null || inn === out) { box.style.display = 'none'; return; }

        const night = out < inn;
        const len = night ? out + 1440 - inn : out - inn;
        const late = mins($('shLate').value) ?? inn;
        const early = mins($('shEarly').value) ?? out;
        const before = +$('shBefore').value || 0, after = +$('shAfter').value || 0;
        const outAbs = inn + len, winStart = inn - before, winEnd = outAbs + after;
        const span = before + len + after;

        box.style.display = '';
        box.className = 'alert small mb-0 ' + (span > 1440 ? 'alert-danger' : 'alert-light border');
        box.innerHTML =
            `<i class="fas fa-${night ? 'moon' : 'sun'} mr-1"></i><b>${night ? 'Night shift' : 'Day shift'}:</b> ` +
            `${fmt(inn)} → ${fmt(out)}${night ? ' <b>(next day)</b>' : ''}, ${Math.floor(len / 60)}h ${String(len % 60).padStart(2, '0')}m. ` +
            `Late after <b>${fmt(late)}</b>, early out before <b>${fmt(early)}</b>. ` +
            `Punches from <b>${fmt(winStart)}${day(0, winStart)}</b> to <b>${fmt(winEnd)}${day(0, winEnd)}</b> are reported on the shift's start day.` +
            (span > 1440 ? ' <br><b>Window is longer than 24 hours, reduce the before/after minutes.</b>' : '');
    }
    ['shIn', 'shOut', 'shLate', 'shEarly', 'shBefore', 'shAfter'].forEach(id => {
        $(id).addEventListener('change', render);
        $(id).addEventListener('input', render);
    });
    document.addEventListener('DOMContentLoaded', render);
    render();
})();
</script>
@endpush
