@extends('layouts.app')
@section('title', 'Dashboard')

@push('css')
<style>
    /* ═══ Live attendance dashboard (one institute) ═══ */
    .ld { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; padding: 16px 18px; box-shadow: 0 6px 24px rgba(15, 23, 42, .06); }
    .ld-head { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid #1e1b4b; padding-bottom: 10px; margin-bottom: 12px; }
    .ld-logo { width: 110px; height: 64px; display: flex; align-items: center; justify-content: flex-start; flex: 0 0 110px; }
    .ld-logo img { max-width: 74px; max-height: 64px; object-fit: contain; }
    .ld-title { flex: 1; min-width: 0; text-align: center; }
    .ld-name { font-size: 26px; font-weight: 700; color: #111827; line-height: 1.2; }
    .ld-addr { color: #4b5563; font-size: 13px; }
    .ld-clock { flex: 0 0 110px; white-space: nowrap; text-align: right; font-weight: 700; color: #4338ca; font-size: 15px; font-variant-numeric: tabular-nums; }

    .ld-grid { display: grid; grid-template-columns: 220px minmax(0, 1fr) 240px; gap: 16px; }
    .ld-filter { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; margin-bottom: 12px; }
    .ld-filter select { width: 240px; }
    .ld-label { font-weight: 800; font-size: 17px; letter-spacing: .5px; color: #111827; text-transform: uppercase; }
    .ld-label.absent { color: #b91c1c; text-align: center; }
    .ld-label.present { color: #047857; }

    /* left: report */
    .ld-date { font-weight: 700; margin-bottom: 18px; }
    .ld-date span { color: #4338ca; }
    .ld-stat { display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; border-radius: 10px;
        margin-bottom: 8px; font-weight: 600; font-size: 14px; background: #f8fafc; border: 1px solid #eef2f7; }
    .ld-stat b { font-size: 20px; font-variant-numeric: tabular-nums; }
    .ld-stat.present b { color: #047857; } .ld-stat.absent b { color: #b91c1c; }
    .ld-stat.late b { color: #d97706; } .ld-stat.leave b { color: #6b7280; } .ld-stat.pct b { color: #4338ca; }
    .ld-bar { height: 8px; border-radius: 6px; background: #fee2e2; overflow: hidden; margin-top: 4px; }
    .ld-bar > div { height: 100%; background: linear-gradient(90deg, #10b981, #059669); transition: width .6s ease; }

    /* centre: spotlight of absent people */
    .ld-spot { border: 2px solid #1e1b4b; border-radius: 12px; height: 540px; max-width: 460px; margin: 0 auto; display: flex; flex-direction: column;
        align-items: center; justify-content: center; text-align: center; padding: 16px; position: relative; overflow: hidden;
        background: radial-gradient(circle at 50% 30%, #fef2f2, #fff 70%); }
    .ld-spot-photo { width: 240px; height: 240px; border-radius: 12px; object-fit: cover; object-position: center top; border: 4px solid #fecaca;
        box-shadow: 0 10px 30px rgba(185, 28, 28, .18); background: #f3f4f6; }
    .ld-spot-initial { width: 240px; height: 240px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto;
        font-size: 100px; font-weight: 800; color: #b91c1c; background: #fee2e2; border: 4px solid #fecaca; }
    .ld-spot-name { font-size: 22px; font-weight: 800; margin-top: 12px; color: #111827; max-width: 100%; }
    .ld-spot-name a { color: inherit; text-decoration: none; border-bottom: 2px dashed #a5b4fc; }
    .ld-spot-name a:hover { color: #4338ca; }
    .ld-spot-time { margin-top: 6px; font-size: 14px; color: #374151; }
    .ld-spot-time b { color: #111827; }
    .ld-spot.present { background: radial-gradient(circle at 50% 30%, #ecfdf5, #fff 70%); }
    .ld-spot.present .ld-spot-photo { border-color: #a7f3d0; box-shadow: 0 10px 30px rgba(4, 120, 87, .18); }
    .ld-spot.present .ld-spot-initial { background: #d1fae5; color: #047857; border-color: #a7f3d0; }
    .ld-spot-tag.present { background: #047857; } .ld-spot-tag.late { background: #d97706; }
    .ld-spot-back { position: absolute; left: 12px; top: 8px; font-size: 12px; color: #6b7280; cursor: pointer; display: none; }
    .ld-spot-back:hover { color: #111827; }
    .ld-spot.pinned .ld-spot-back { display: block; }
    .ld-spot-sub { color: #6b7280; font-size: 14px; }
    .ld-spot-tag { display: inline-block; margin-top: 6px; padding: 2px 12px; border-radius: 20px; background: #b91c1c; color: #fff; font-size: 12px; font-weight: 700; }
    .ld-spot-tag.upcoming { background: #0284c7; }
    .ld-spot-count { position: absolute; right: 12px; bottom: 8px; font-size: 12px; color: #9ca3af; }
    .ld-spot-inner { transition: opacity .45s ease, transform .45s ease; }
    .ld-spot-inner.fade { opacity: 0; transform: scale(.97); }
    .ld-spot-ok { color: #047857; font-size: 22px; font-weight: 700; }
    .ld-spot-ok i { font-size: 60px; display: block; margin-bottom: 10px; }

    /* person card (absent column + present row) */
    .ld-card { width: 220px; border: 1px solid #d1d5db; border-radius: 12px; padding: 10px; background: #fff; display: flex; flex-direction: column;
        align-items: center; text-align: center; gap: 6px; flex: 0 0 auto; cursor: pointer; transition: box-shadow .15s ease, transform .15s ease; }
    .ld-card:hover { box-shadow: 0 6px 18px rgba(15, 23, 42, .15); transform: translateY(-2px); }
    .ld-card img, .ld-card .ini { width: 200px; height: 200px; border-radius: 10px; object-fit: cover; object-position: center top; flex: 0 0 200px; }
    .ld-card .ini { display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 80px; background: #eef2ff; color: #4338ca; }
    .ld-card .nm { font-weight: 700; font-size: 15px; line-height: 1.25; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ld-card .tm { font-size: 13px; color: #4b5563; white-space: nowrap; }
    .ld-card .tm b { color: #111827; } .ld-card .late { color: #dc2626 !important; }
    .ld-card.absent { border-color: #fecaca; background: #fff7f7; } .ld-card.absent .ini { background: #fee2e2; color: #b91c1c; }
    .ld-card.present { border-color: #a7f3d0; background: #f6fffb; } .ld-card.present .ini { background: #d1fae5; color: #047857; }
    .ld-card > div { min-width: 0; width: 100%; }

    /* right: absent list scrolling up */
    .ld-vwin { height: 540px; overflow: hidden; position: relative; }
    .ld-vtrack { display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .ld-vtrack.run { animation: ld-up var(--dur, 30s) linear infinite; }
    @keyframes ld-up { from { transform: translateY(0); } to { transform: translateY(-50%); } }

    /* bottom: present list scrolling left */
    .ld-present { display: flex; align-items: center; gap: 14px; margin-top: 16px; border-top: 1px dashed #d1d5db; padding-top: 14px; }
    .ld-hwin { flex: 1; min-width: 0; overflow: hidden; }
    .ld-htrack { display: flex; gap: 10px; width: max-content; }
    .ld-htrack.run { animation: ld-left var(--dur, 30s) linear infinite; }
    @keyframes ld-left { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .ld-vwin:hover .run, .ld-hwin:hover .run { animation-play-state: paused; }
    .ld-empty { color: #9ca3af; font-size: 13px; padding: 12px; text-align: center; }

    @media (max-width: 991px) {
        .ld-grid { grid-template-columns: 1fr; }
        .ld-vwin { height: 300px; }
        .ld-spot { height: 460px; }
    }
</style>
@endpush

@section('content')
    @if (isSuperAdmin())
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <h4 class="mb-2"><i class="fas fa-tv mr-2 text-muted"></i>Live Attendance Dashboard</h4>
            <form method="GET" action="{{ route('dashboard') }}" style="min-width:300px" class="mb-2">
                @include('partials.institute_select', ['autoSubmit' => true])
            </form>
        </div>
    @endif

    @if (! $institute)
        {{-- super admin: empty until an institute is chosen --}}
        <div class="ld text-center py-5">
            <i class="fas fa-university fa-3x text-muted mb-3" style="opacity:.35"></i>
            <h5 class="text-muted mb-1">Select an institute</h5>
            <p class="text-muted mb-0">Choose an institute from the dropdown above to see its live attendance dashboard.</p>
        </div>
    @else
        <div class="ld" id="liveDash"
             data-url="{{ route('attendance-board.data', ['institute_id' => $institute]) }}"
             data-profile-url="{{ userCan('teacher-profiles') ? route('teacher-profiles.show', '__ID__') : '' }}"
             data-refresh-ms="60000" data-spot-ms="5000" data-pin-ms="30000">

            {{-- ── Filters: department and / or shift (data reloads without a page refresh) ── --}}
            <div class="ld-filter">
                <select class="form-control form-control-sm" data-ld="department" aria-label="Department">
                    <option value="">All Departments</option>
                    @foreach ($departments as $d)
                        <option value="{{ $d->id }}" data-shift="{{ $d->shift_id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                <select class="form-control form-control-sm" data-ld="shift" aria-label="Shift">
                    <option value="">All Shifts</option>
                    @foreach ($shifts as $sh)
                        <option value="{{ $sh->id }}">{{ $sh->title }} ({{ \App\Services\AttendanceReportService::shiftClock($sh->in_time) }} - {{ \App\Services\AttendanceReportService::shiftClock($sh->out_time, $sh->in_time) }})</option>
                    @endforeach
                </select>
            </div>

            {{-- ── Letterhead ── --}}
            <div class="ld-head">
                <div class="ld-logo">
                    @if ($school->logo_url)<img src="{{ $school->logo_url }}" alt="">@else<i class="fas fa-school fa-2x text-muted"></i>@endif
                </div>
                <div class="ld-title">
                    <div class="ld-name fit-one-line">{{ $school->name }}</div>
                    @if ($school->address)<div class="ld-addr">{{ $school->address }}</div>@endif
                </div>
                <div class="ld-clock" data-ld="clock">--:--</div>
            </div>

            <div class="ld-grid">
                {{-- ── Left: date + report ── --}}
                <div>
                    <div class="ld-date">Date : <span data-ld="date">{{ now()->format('d M Y, l') }}</span></div>
                    <div class="ld-label mb-2">Report</div>
                    <div class="ld-stat present">Total Present <b data-ld="present">0</b></div>
                    <div class="ld-stat absent">Total Absent <b data-ld="absent">0</b></div>
                    <div class="ld-stat late">Total Late <b data-ld="late">0</b></div>
                    <div class="ld-stat leave" title="No leave module yet">Total Leave <b data-ld="leave">-</b></div>
                    <div class="ld-stat pct">Percentage <b data-ld="pct">0%</b></div>
                    <div class="ld-bar"><div data-ld="bar" style="width:0%"></div></div>
                    <div class="small text-muted mt-2">
                        Teachers/Staff: <b data-ld="total">0</b> &middot; <span data-ld="updated">loading…</span>
                    </div>
                </div>

                {{-- ── Centre: absent spotlight ── --}}
                <div>
                    <div class="ld-label absent mb-2" data-ld="spot-label">Absent</div>
                    <div class="ld-spot" data-ld="spot">
                        <span class="ld-spot-back" data-ld="spot-back"><i class="fas fa-undo mr-1"></i>back to rotation</span>
                        <div class="ld-spot-inner" data-ld="spot-inner"><div class="ld-empty">Loading…</div></div>
                        <div class="ld-spot-count" data-ld="spot-count"></div>
                    </div>
                </div>

                {{-- ── Right: absent list, scrolling up ── --}}
                <div>
                    <div class="ld-label absent mb-2">Absent <i class="fas fa-arrow-up small"></i></div>
                    <div class="ld-vwin"><div class="ld-vtrack" data-ld="absent-track"></div></div>
                </div>
            </div>

            {{-- ── Bottom: present list, scrolling left ── --}}
            <div class="ld-present">
                <div class="ld-label present" style="flex:0 0 auto">Present <i class="fas fa-arrow-left small"></i></div>
                <div class="ld-hwin"><div class="ld-htrack" data-ld="present-track"></div></div>
            </div>
        </div>
    @endif
@endsection

@push('js')
<script>
(function () {
    const root = document.getElementById('liveDash');
    if (!root) return;

    const $ = k => root.querySelector('[data-ld="' + k + '"]');
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const SEC_PER_CARD = 4;                       // marquee speed: seconds each card takes to pass
    const PIN_MS  = +root.dataset.pinMs || 30000; // a clicked person stays in the centre this long
    const SPOT_MS = +root.dataset.spotMs || 5000; // rotation of absentees in the centre
    let absent = [], people = {}, spotIdx = 0, spotTimer = null, pinTimer = null, lastSig = '';

    // ── clock ──
    function tick() {
        $('clock').textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    tick(); setInterval(tick, 1000);

    const isPresent = t => t.status === 'late' || t.status === 'on_time';

    function card(t, kind) {
        const inT  = t.in_time ? `<span class="${t.status === 'late' ? 'late' : ''}">${esc(t.in_time)}</span>` : '-';
        const outT = t.out_time ? esc(t.out_time) : '-';
        const pic  = t.image ? `<img src="${esc(t.image)}" alt="" loading="lazy">` : `<div class="ini">${esc(t.initial)}</div>`;
        return `<div class="ld-card ${kind}" data-id="${t.id}" title="Click to show ${esc(t.name)} in the centre">
                    ${pic}
                    <div><div class="nm">${esc(t.name)}</div>
                    <div class="tm">In: <b>${inT}</b> &nbsp; Out: <b>${outT}</b></div></div>
                </div>`;
    }

    // Seamless marquee: content is doubled and moved by half its length.
    function marquee(track, items, kind, win, vertical) {
        track.classList.remove('run');
        if (!items.length) {
            track.innerHTML = `<div class="ld-empty">${kind === 'absent' ? 'No absentees' : 'No one present yet'}</div>`;
            return;
        }
        const html = items.map(t => card(t, kind)).join('');
        track.innerHTML = html;
        const overflow = vertical ? track.scrollHeight > win.clientHeight : track.scrollWidth > win.clientWidth;
        if (overflow) {
            track.innerHTML = html + html;
            track.style.setProperty('--dur', Math.max(12, items.length * SEC_PER_CARD) + 's');
            track.classList.add('run');
        }
    }

    // ── centre: one person (absentees rotate; a clicked person is pinned) ──
    function render(t, pinned) {
        const spot  = $('spot'), inner = $('spot-inner');
        const here  = isPresent(t);
        const tag   = t.status === 'upcoming' ? ['upcoming', 'Shift not started']
                    : t.status === 'late' ? ['late', 'Late'] : here ? ['present', 'Present'] : ['', 'Absent'];
        const url   = root.dataset.profileUrl ? root.dataset.profileUrl.replace('__ID__', t.id) : '';
        const name  = url ? `<a href="${esc(url)}" target="_blank" rel="noopener" title="Open Teacher/Staff Profile in a new tab">${esc(t.name)} <i class="fas fa-external-link-alt" style="font-size:.55em"></i></a>` : esc(t.name);
        const times = here ? `<div class="ld-spot-time">In: <b>${esc(t.in_time || '-')}</b> &nbsp;·&nbsp; Out: <b>${esc(t.out_time || '-')}</b>${t.late_by ? ` &nbsp;·&nbsp; <span class="text-danger">late ${esc(t.late_by)}</span>` : ''}</div>` : '';

        spot.classList.toggle('present', here);
        spot.classList.toggle('pinned', !!pinned);
        $('spot-label').textContent = pinned ? (here ? 'Present' : 'Absent') : 'Absent';
        $('spot-label').style.color = here && pinned ? '#047857' : '';

        inner.classList.add('fade');
        setTimeout(() => {
            inner.innerHTML = `
                ${t.image ? `<img src="${esc(t.image)}" alt="" class="ld-spot-photo">` : `<div class="ld-spot-initial">${esc(t.initial)}</div>`}
                <div class="ld-spot-name fit-one-line">${name}</div>
                <div class="ld-spot-sub">${esc([t.designation, t.department].filter(Boolean).join(' · '))}</div>
                <div class="ld-spot-sub">ID: ${esc(t.teacher_no)}${t.shift_in ? ' · Shift ' + esc(t.shift_in) + ' - ' + esc(t.shift_out || '') : ''}</div>
                ${times}
                <span class="ld-spot-tag ${tag[0]}">${tag[1]}</span>`;
            inner.classList.remove('fade');
            if (window.fitOneLine) window.fitOneLine();
        }, 250);
    }

    function rotate() {
        if (pinTimer) return; // someone is pinned
        const spot = $('spot');
        spot.classList.remove('pinned', 'present');
        $('spot-label').textContent = 'Absent';
        $('spot-label').style.color = '';
        if (!absent.length) {
            $('spot-inner').innerHTML = `<div class="ld-spot-ok"><i class="fas fa-award"></i>No absentees today<br><small class="text-muted">Full attendance!</small></div>`;
            $('spot-count').textContent = '';
            return;
        }
        spotIdx = spotIdx % absent.length;
        render(absent[spotIdx], false);
        $('spot-count').textContent = (spotIdx + 1) + ' / ' + absent.length;
        spotIdx++;
    }

    function startRotation() {
        clearTimeout(pinTimer); pinTimer = null;
        clearInterval(spotTimer);
        rotate();
        spotTimer = setInterval(rotate, SPOT_MS);
    }

    // click a card (present or absent) → show that person in the centre
    root.addEventListener('click', function (e) {
        const c = e.target.closest('.ld-card');
        if (!c || !people[c.dataset.id]) return;
        clearInterval(spotTimer);
        clearTimeout(pinTimer);
        render(people[c.dataset.id], true);
        $('spot-count').textContent = '';
        pinTimer = setTimeout(startRotation, PIN_MS); // back to the absent rotation after a while
    });
    $('spot-back').addEventListener('click', startRotation);

    // ── filters ──
    const dept = $('department'), shift = $('shift');
    const allShifts = Array.from(shift.options).map(o => o.cloneNode(true));
    function limitShifts() { // a department follows one shift
        const opt = dept.options[dept.selectedIndex];
        const only = opt && opt.value ? opt.dataset.shift : '';
        const keep = shift.value;
        shift.innerHTML = '';
        allShifts.forEach(o => { if (!o.value || !only || o.value === only) shift.appendChild(o.cloneNode(true)); });
        shift.value = Array.from(shift.options).some(o => o.value === keep) ? keep : '';
    }
    dept.addEventListener('change', () => { limitShifts(); lastSig = ''; load(); });
    shift.addEventListener('change', () => { lastSig = ''; load(); });

    function load() {
        const params = new URLSearchParams({ _: Date.now() });
        if (dept.value) params.set('department_id', dept.value);
        if (shift.value) params.set('shift_id', shift.value);
        return fetch(root.dataset.url + '&' + params, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                const s = data.summary || {};
                const list = data.teachers || [];
                const counted = Math.max(0, (s.total || 0) - (s.upcoming || 0)); // shift not started yet → not counted
                const pct = counted ? Math.round((s.present || 0) / counted * 1000) / 10 : 0;

                $('date').textContent = data.date || '';
                $('present').textContent = s.present || 0;
                $('absent').textContent = s.absent || 0;
                $('late').textContent = s.late || 0;
                $('pct').textContent = pct + '%';
                $('bar').style.width = pct + '%';
                $('total').textContent = s.total || 0;
                $('updated').textContent = 'updated ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const sig = JSON.stringify(list);
                if (sig === lastSig) return; // nothing changed: keep the marquees running smoothly
                lastSig = sig;

                people = {};
                list.forEach(t => { people[t.id] = t; });
                // present: arrival order (as sent); absent: absent first, then shift-not-started
                const present = list.filter(isPresent);
                absent = list.filter(t => t.status === 'absent').concat(list.filter(t => t.status === 'upcoming'));

                marquee($('present-track'), present, 'present', root.querySelector('.ld-hwin'), false);
                marquee($('absent-track'), absent, 'absent', root.querySelector('.ld-vwin'), true);

                if (!pinTimer) { spotIdx = 0; startRotation(); }
            })
            .catch(() => { $('updated').textContent = 'connection problem, retrying…'; });
    }

    load();
    setInterval(load, +root.dataset.refreshMs || 60000);
})();
</script>
@endpush
