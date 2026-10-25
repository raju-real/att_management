{{--
    On-screen report heading: the selected institute's logo, name, address
    and contact line (schoolInfo), the report title and the period.
    Admin without an institute selected → Site Settings + "All Institutes".

    @include('partials.report_heading', ['title' => 'Attendance Logs', 'period' => '01 Oct 2026 – 25 Oct 2026'])
--}}
@php $school = schoolInfo(selectedInstituteId()); @endphp
<div class="card admin-card mb-3 report-heading">
    <div class="card-body py-2">
        <div class="d-flex align-items-center">
            <div style="width:80px" class="text-center">
                @if ($school->logo_url)
                    <img src="{{ $school->logo_url }}" alt="" style="max-height:56px;max-width:76px;object-fit:contain">
                @else
                    <i class="fas fa-university fa-2x text-muted"></i>
                @endif
            </div>
            <div class="flex-grow-1 text-center">
                <div style="font-size:1.25rem;font-weight:600;line-height:1.2">{{ $school->name }}</div>
                @if ($school->address)<div class="text-muted small">{{ $school->address }}</div>@endif
                @if ($school->contact_line)<div class="text-muted" style="font-size:.75rem">{{ $school->contact_line }}</div>@endif
                <div class="mt-1" style="font-weight:600">
                    {{ $title }}
                    @if (! $school->is_institute && isSuperAdmin())
                        <span class="badge badge-warning ml-1">All Institutes</span>
                    @endif
                </div>
                @if (! empty($period))<div class="small text-muted">{{ $period }}</div>@endif
            </div>
            <div style="width:80px"></div>
        </div>
    </div>
</div>
