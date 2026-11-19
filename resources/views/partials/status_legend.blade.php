{{--
    The day-status key shown at the top of every attendance report:
    P = Present · A = Absent · L = Leave · H = Holiday

    @include('partials.status_legend', ['mode' => 'web'])   // 'web' | 'pdf' | 'xlsx'
--}}
@php
    $mode  = $mode ?? 'web';
    $small = $mode === 'pdf' ? 'font-size:7pt;' : 'font-size:12px;';
    $hints = [
        'P' => 'Present: attended (in / out time shown)',
        'A' => 'Absent: a working day without attendance or leave',
        'L' => 'Leave: on an approved leave',
        'H' => 'Holiday: weekly or office holiday of the institute',
    ];
@endphp
@if ($mode === 'web')
    <div class="status-legend d-flex flex-wrap align-items-center" style="gap:12px;{{ $small }}color:#4b5563;">
        <span class="font-weight-bold text-dark">Status:</span>
        @foreach (\App\Services\LeaveService::CODES as $code => [$label, $color])
            <span title="{{ $hints[$code] }}" style="cursor:help;white-space:nowrap">
                <b style="display:inline-block;min-width:20px;text-align:center;border-radius:4px;padding:0 5px;color:#fff;background:{{ $color }};">{{ $code }}</b>
                {{ $label }}
            </span>
        @endforeach
    </div>
@else
    <div style="{{ $small }}color:#333;margin:2px 0 4px;">
        <b>Status:</b>
        @foreach (\App\Services\LeaveService::CODES as $code => [$label, $color])
            <b style="color:{{ $color }};">{{ $code }}</b> = {{ $label }}@if (! $loop->last) &nbsp;·&nbsp; @endif
        @endforeach
    </div>
@endif
