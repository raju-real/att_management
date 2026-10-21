{{-- Signature lines under each report section (web page and Excel). --}}
@php $xlsx = ($mode ?? 'web') === 'xlsx'; @endphp
<table style="width:100%;border-collapse:separate;border-spacing:40px 0;margin-top:{{ $xlsx ? '0' : '40px' }};">
    @if($xlsx)
        <tr><td></td></tr><tr><td></td></tr>
    @endif
    <tr>
        @foreach(['Prepared By', 'Checked By', 'Head Teacher / Principal'] as $who)
            <td style="width:33%;text-align:center;border-top:1px solid #000;padding-top:3px;">{{ $who }}</td>
        @endforeach
    </tr>
</table>
