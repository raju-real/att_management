{{-- Excel export: same sections as the page/PDF, one after another. --}}
<table>
    <tr><td></td></tr>
</table>
@foreach($sheet['sections'] as $section)
    @include('attendance.sheet._section', ['sheet' => $sheet, 'section' => $section, 'mode' => 'xlsx'])
    <table><tr><td></td></tr><tr><td></td></tr></table>
@endforeach
