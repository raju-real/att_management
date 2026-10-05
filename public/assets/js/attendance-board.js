/**
 * Teacher Attendance Board — the single slider/renderer used by the
 * dashboard board and the standalone /attendance-board page.
 *
 * Markup:  resources/views/attendance/board/_board.blade.php
 * Styles:  public/assets/css/attendance-board.css
 * Data:    GET attendance-board/data?date=YYYY-MM-DD
 *          (AttendanceReportService::teacherBoard)
 *
 * Usage:   AttendanceBoard.init(document.querySelector('[data-tb-board]'));
 * Options are read from data-* attributes on the root element:
 *   data-url, data-detailed ("1"), data-keys ("1"), data-slide-ms, data-refresh-ms, data-card-min
 */
(function (window, document) {
    'use strict';

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const todayStr = () => {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    };

    const EMPTY_MSG = {
        late: 'No late arrivals. Everyone is on time!',
        early_out: 'Nobody left early.',
        absent: 'No absentees. Full attendance!',
        on_time: 'Nobody has arrived on time yet.',
        present: 'No one has punched in yet.',
    };

    // ── One card template; `detailed` adds shift + in/out + status chips ──
    function card(t, i, detailed) {
        const photo = t.image
            ? `<img src="${esc(t.image)}" alt="" loading="lazy">`
            : `<div class="ph">${esc(t.initial)}</div>`;
        const head = `<div class="tb-photo">${photo}</div>
                      <div class="tb-name">${esc(t.name)}</div>
                      <span class="tb-no">#${esc(t.teacher_no)}</span>
                      <div class="tb-dept">${esc(t.department || t.designation || '')}&nbsp;</div>`;
        const cls = `tb-card ${t.status}${t.early_out ? ' early_out' : ''}`;
        const style = `animation-delay:${(i * 0.045).toFixed(3)}s`;

        if (!detailed) {
            let time, tag;
            if (t.status === 'absent') {
                time = 'Absent';
                tag = `<span class="tb-tag">${t.shift_in ? 'Shift ' + esc(t.shift_in) : 'No punch'}</span>`;
            } else if (t.status === 'late') {
                time = esc(t.in_time);
                tag = `<span class="tb-tag"><i class="fas fa-exclamation-circle mr-1"></i>Late ${esc(t.late_by)}</span>`;
            } else {
                time = esc(t.in_time);
                tag = `<span class="tb-tag"><i class="fas fa-check mr-1"></i>On Time</span>`;
            }
            return `<div class="${cls}" style="${style}" title="${esc(t.name)}${t.out_time ? ' | Out ' + esc(t.out_time) : ''}">
                        ${head}<div class="tb-time">${time}</div>${tag}</div>`;
        }

        const shift = t.shift_in
            ? `<div class="tb-shift"><i class="fas fa-business-time mr-1"></i>${esc(t.shift_title || 'Shift')}: ${esc(t.shift_in)} - ${esc(t.shift_out)}</div>`
            : `<div class="tb-shift">No shift assigned</div>`;

        const inCls  = t.status === 'absent' ? 'none' : (t.status === 'late' ? 'bad' : 'ok');
        const outCls = !t.out_time ? 'none' : (t.early_out ? 'bad' : 'ok');
        const io = `<div class="tb-io">
                        <div><span class="lbl">In</span><span class="val ${inCls}">${t.in_time ? esc(t.in_time) : '-'}</span></div>
                        <div><span class="lbl">Out</span><span class="val ${outCls}">${t.out_time ? esc(t.out_time) : '-'}</span></div>
                    </div>`;

        const tags = [];
        if (t.status === 'absent') tags.push(`<span class="tb-tag">Absent</span>`);
        if (t.status === 'on_time') tags.push(`<span class="tb-tag"><i class="fas fa-check mr-1"></i>On Time</span>`);
        if (t.status === 'late') tags.push(`<span class="tb-tag"><i class="fas fa-exclamation-circle mr-1"></i>Late In ${esc(t.late_by)}</span>`);
        if (t.early_out) tags.push(`<span class="tb-tag t-early"><i class="fas fa-sign-out-alt mr-1"></i>Early Out ${esc(t.early_out_by)}</span>`);

        return `<div class="${cls}" style="${style}" title="${esc(t.name)}">
                    ${head}${shift}${io}
                    <div class="tb-tags">${tags.join('')}</div>
                    ${t.work_hours ? `<div class="tb-hours"><i class="far fa-clock mr-1"></i>${esc(t.work_hours)} worked</div>` : ''}
                </div>`;
    }

    function init(root) {
        if (!root || root.__tbInit) return;
        root.__tbInit = true;

        const $ = name => root.querySelector(`[data-tb="${name}"]`);
        const opt = {
            url: root.dataset.url,
            detailed: root.dataset.detailed === '1',
            slideMs: +root.dataset.slideMs || 7000,
            refreshMs: +root.dataset.refreshMs || 60000,
            cardMin: +root.dataset.cardMin || 150,
            keys: root.dataset.keys === '1',
        };

        const track = $('track'), viewport = $('viewport'), dotsBox = $('dots'), pageLbl = $('page');
        const progress = $('progress'), dateInput = $('date'), tabs = $('tabs'), loader = $('loader');

        let all = [], filter = 'all', slides = 1, current = 0, playing = true;
        let timer = null, rafId = null, progressStart = 0;

        // ── Clock ──
        const clock = $('clock');
        const tick = () => {
            const d = new Date();
            let h = d.getHours(); const ap = h >= 12 ? 'PM' : 'AM'; h = (h % 12) || 12;
            clock.innerHTML = String(h).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') +
                ':' + String(d.getSeconds()).padStart(2, '0') + '<small>' + ap + '</small>';
        };
        if (clock) { tick(); setInterval(tick, 1000); }

        // ── How many cards fit per slide ──
        function layout() {
            const small = window.innerWidth < 576;
            const w = viewport.clientWidth - (small ? 28 : 52);
            const minCard = small ? Math.min(opt.cardMin, 140) : opt.cardMin;
            const cols = Math.max(2, Math.min(10, Math.floor((w + 14) / (minCard + 14))));
            const cardH = measuredH || (opt.detailed ? 340 : 235);
            let rows = small ? 3 : 2;
            if (document.fullscreenElement === root) {
                rows = Math.max(1, Math.floor((window.innerHeight - 230) / cardH));
            } else if (opt.detailed && !small) {
                // standalone page: fill the visible height, never fewer than 2 rows
                rows = Math.max(2, Math.floor((window.innerHeight - viewport.getBoundingClientRect().top - 40) / cardH));
            }
            return { cols, rows, per: cols * rows };
        }

        function filtered() {
            switch (filter) {
                case 'all': return all;
                case 'present': return all.filter(t => t.status !== 'absent');
                case 'early_out': return all.filter(t => t.early_out);
                default: return all.filter(t => t.status === filter);
            }
        }

        // Real rendered card height (+ grid gap), measured once cards exist,
        // so "rows that fit" follows the actual design instead of a guess.
        let measuredH = 0;
        function measure() {
            const c = track.querySelector('.tb-card');
            if (!c) return false;
            const h = c.offsetHeight + 14;
            if (Math.abs(h - measuredH) < 4) return false;
            measuredH = h;
            return true;
        }

        function render(keepSlide, remeasured) {
            const list = filtered();
            const { cols, per } = layout();
            slides = Math.max(1, Math.ceil(list.length / per));
            if (!keepSlide || current >= slides) current = 0;

            if (!list.length) {
                track.innerHTML = `<div class="tb-slide is-active" style="grid-template-columns:1fr"><div class="tb-empty"><i class="fas fa-award"></i>${EMPTY_MSG[filter] || 'No teachers found.'}</div></div>`;
            } else {
                let html = '';
                for (let s = 0; s < slides; s++) {
                    const chunk = list.slice(s * per, (s + 1) * per);
                    html += `<div class="tb-slide" style="grid-template-columns:repeat(${cols},minmax(0,1fr))">${chunk.map((t, i) => card(t, i, opt.detailed)).join('')}</div>`;
                }
                track.innerHTML = html;
                if (!remeasured && measure() && layout().per !== per) {
                    return render(keepSlide, true);
                }
            }

            dotsBox.innerHTML = slides > 1
                ? Array.from({ length: slides }, (_, i) => `<button type="button" class="tb-dot" data-i="${i}" aria-label="Slide ${i + 1}"></button>`).join('')
                : '';
            go(current, true);
        }

        function go(i, instant) {
            current = (i + slides) % slides;
            if (instant) track.style.transition = 'none';
            track.style.transform = `translateX(-${current * 100}%)`;
            if (instant) { void track.offsetWidth; track.style.transition = ''; }

            track.querySelectorAll('.tb-slide').forEach((s, idx) => {
                s.classList.remove('is-active');
                if (idx === current) { void s.offsetWidth; s.classList.add('is-active'); }
            });
            dotsBox.querySelectorAll('.tb-dot').forEach((d, idx) => d.classList.toggle('active', idx === current));

            const n = filtered().length, per = layout().per;
            pageLbl.textContent = n ? `Showing ${current * per + 1}–${Math.min(n, (current + 1) * per)} of ${n}  ·  Slide ${current + 1}/${slides}` : '';
            restartAuto();
        }

        // ── Autoplay + progress bar ──
        function restartAuto() {
            clearTimeout(timer); cancelAnimationFrame(rafId);
            progress.style.width = '0';
            if (!playing || slides < 2) return;
            progressStart = performance.now();
            const step = now => {
                progress.style.width = Math.min(100, (now - progressStart) / opt.slideMs * 100) + '%';
                if (now - progressStart < opt.slideMs) rafId = requestAnimationFrame(step);
            };
            rafId = requestAnimationFrame(step);
            timer = setTimeout(() => go(current + 1), opt.slideMs);
        }

        function setPlaying(p) {
            playing = p;
            const btn = $('play');
            btn.innerHTML = p ? '<i class="fas fa-pause"></i>' : '<i class="fas fa-play"></i>';
            btn.title = p ? 'Pause' : 'Play';
            restartAuto();
        }

        // ── Data ──
        function setCount(key, val) {
            const elc = root.querySelector(`[data-tb-count="${key}"]`);
            if (elc) elc.textContent = val ?? 0;
        }

        let signature = '', lastLoad = 0, busy = false;

        /**
         * @param keepSlide stay on the current slide if it still exists
         * @param silent    background refresh: no loader, and the cards are
         *                  only re-rendered when the data actually changed
         */
        function load(keepSlide, silent) {
            if (busy && silent) return Promise.resolve();
            busy = true;
            if (!silent) loader.classList.add('show');
            const params = new URLSearchParams({ date: dateInput.value });
            return fetch(opt.url + '?' + params, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(data => {
                    lastLoad = Date.now();
                    const s = data.summary || {};
                    $('date-label').textContent = data.date;
                    setCount('all', s.total);
                    ['present', 'on_time', 'late', 'early_out', 'absent'].forEach(k => setCount(k, s[k]));
                    $('live').style.display = dateInput.value === todayStr() ? '' : 'none';
                    setUpdated(true);

                    const sig = JSON.stringify(data.teachers || []);
                    if (!silent || sig !== signature) {
                        signature = sig;
                        all = data.teachers || [];
                        render(keepSlide);
                    }
                    root.dispatchEvent(new CustomEvent('tb:loaded', { detail: data }));
                })
                .catch(err => { setUpdated(false); console.error('Attendance board load failed:', err); })
                .finally(() => { busy = false; loader.classList.remove('show'); });
        }

        function setUpdated(ok) {
            const u = $('updated');
            if (!u) return;
            const d = new Date();
            let h = d.getHours(); const ap = h >= 12 ? 'PM' : 'AM'; h = (h % 12) || 12;
            const t = String(h).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0') + ' ' + ap;
            u.textContent = ok ? 'Updated ' + t : 'Offline, retrying…';
            u.classList.toggle('tb-updated-error', !ok);
        }

        // ── Events ──
        tabs.addEventListener('click', e => {
            const tab = e.target.closest('.tb-tab'); if (!tab) return;
            tabs.querySelectorAll('.tb-tab').forEach(t => t.classList.toggle('active', t === tab));
            filter = tab.dataset.filter;
            render(false);
        });
        $('prev').addEventListener('click', () => go(current - 1));
        $('next').addEventListener('click', () => go(current + 1));
        $('play').addEventListener('click', () => setPlaying(!playing));
        dotsBox.addEventListener('click', e => { const d = e.target.closest('.tb-dot'); if (d) go(+d.dataset.i); });

        const fullBtn = $('full');
        if (fullBtn) {
            fullBtn.addEventListener('click', () => {
                if (document.fullscreenElement) document.exitFullscreen();
                else if (root.requestFullscreen) root.requestFullscreen();
            });
            document.addEventListener('fullscreenchange', () => {
                fullBtn.innerHTML = document.fullscreenElement ? '<i class="fas fa-compress"></i>' : '<i class="fas fa-expand"></i>';
                setTimeout(() => render(false), 150);
            });
        }

        dateInput.addEventListener('change', () => load(false));
        if (window.flatpickr && !dateInput._flatpickr) {
            window.flatpickr(dateInput, { dateFormat: 'Y-m-d', maxDate: 'today', disableMobile: true });
        }

        // Pause while the pointer is over the cards
        viewport.addEventListener('mouseenter', () => { if (playing) { clearTimeout(timer); cancelAnimationFrame(rafId); } });
        viewport.addEventListener('mouseleave', () => restartAuto());

        // Swipe on touch screens
        let touchX = null;
        viewport.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
        viewport.addEventListener('touchend', e => {
            if (touchX === null) return;
            const dx = e.changedTouches[0].clientX - touchX;
            if (Math.abs(dx) > 40) go(current + (dx < 0 ? 1 : -1));
            touchX = null;
        });

        // Keyboard (standalone/TV use, opt-in via data-keys="1")
        if (opt.keys) document.addEventListener('keydown', e => {
            if (e.target.matches('input, select, textarea')) return;
            if (e.key === 'ArrowRight') go(current + 1);
            else if (e.key === 'ArrowLeft') go(current - 1);
            else if (e.key === ' ') { e.preventDefault(); setPlaying(!playing); }
        });

        let resizeT = null, lastPer = layout().per;
        window.addEventListener('resize', () => {
            clearTimeout(resizeT);
            resizeT = setTimeout(() => { const p = layout().per; if (p !== lastPer) { lastPer = p; render(true); } }, 200);
        });

        // ── Auto refresh every refreshMs (default 60s), no page reload ──
        // Skipped while the browser tab is hidden; catches up as soon as it
        // is visible again. A board left on "today" rolls over at midnight.
        let followingToday = dateInput.value === todayStr();
        dateInput.addEventListener('change', () => { followingToday = dateInput.value === todayStr(); });

        function refresh() {
            if (document.hidden) return;
            if (followingToday && dateInput.value !== todayStr()) {
                const t = todayStr();
                if (dateInput._flatpickr) dateInput._flatpickr.setDate(t, false); else dateInput.value = t;
                return load(false);
            }
            return load(true, true);
        }
        setInterval(refresh, opt.refreshMs);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && Date.now() - lastLoad >= opt.refreshMs) refresh();
        });

        load(false);
        return { reload: () => load(true) };
    }

    window.AttendanceBoard = { init };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-tb-board]').forEach(init);
    });
})(window, document);
