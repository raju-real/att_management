{{--
    Push / remove modal for the teacher & student lists — ONE copy for both.

    @param string $group  'teacher' | 'student'

    Rows:      <input type="checkbox" class="dp-check" value="{id}">
    Header:    <input type="checkbox" id="dpCheckAll">
    Triggers:  data-dp-action="push|remove"  data-dp-scope="one|selected|all"
               data-dp-id="{id}" data-dp-name="{name}" (for scope=one)
--}}
@php
    $dpDevices = app(\App\Services\DevicePushService::class)->devices($group);
    $dpLabel   = ucfirst($group);
@endphp

<div class="modal fade" id="devicePushModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="dpForm" data-push="{{ route('device-push', $group) }}" data-remove="{{ route('device-remove', $group) }}">
            @csrf
            <input type="hidden" name="scope" id="dpScope" value="selected">
            <div id="dpIds"></div>
            <div class="modal-content">
                <div class="modal-header text-white" id="dpHeader" style="background:linear-gradient(90deg,#4f46e5,#7c3aed)">
                    <h5 class="modal-title" id="dpTitle"><i class="fas fa-upload mr-2"></i>Push to Device</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="dpSummary"></p>

                    <div class="form-group">
                        <label class="font-weight-bold">Device
                            <i class="fas fa-question-circle text-muted" title="Only active devices that serve {{ strtolower($dpLabel) }}s are listed"></i>
                        </label>
                        <select name="device_id" class="form-control" id="dpDevice" {{ $dpDevices->isEmpty() ? 'disabled' : '' }}>
                            <option value="">All active devices ({{ $dpDevices->count() }})</option>
                            @foreach($dpDevices as $d)
                                <option value="{{ $d->id }}">{{ $d->name }} — {{ $d->serial_no }}</option>
                            @endforeach
                        </select>
                        @if($dpDevices->isEmpty())
                            <small class="text-danger">No active device serves {{ strtolower($dpLabel) }}s. Check the device list.</small>
                        @endif
                    </div>

                    <div class="alert alert-info small mb-0 py-2" id="dpHelpPush">
                        <i class="fas fa-info-circle mr-1"></i>
                        Users already on the device are <b>skipped</b>; if only the name changed, just the name is updated.
                        Fingerprints, cards and passwords on the device are <b>never changed</b>.
                        Push-mode devices apply it on their next check-in (usually within a minute).
                    </div>
                    <div class="alert alert-danger small mb-0 py-2 d-none" id="dpHelpRemove">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        The user and their <b>fingerprints</b> are deleted from the selected device(s). They will not be able to
                        punch there until pushed and enrolled again. The {{ strtolower($dpLabel) }} record in this system is kept.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="dpSubmit" {{ $dpDevices->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-upload mr-1"></i> Push
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
(function () {
    const LABEL = @json(strtolower($dpLabel));
    const form = document.getElementById('dpForm');
    if (!form) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let mode = 'push', who = '';

    const checks = () => Array.from(document.querySelectorAll('.dp-check'));
    const selected = () => checks().filter(c => c.checked).map(c => c.value);

    function refreshBulk() {
        const n = selected().length;
        document.querySelectorAll('[data-dp-scope="selected"]').forEach(b => {
            b.disabled = n === 0;
            const c = b.querySelector('.dp-count');
            if (c) c.textContent = n;
        });
        const all = document.getElementById('dpCheckAll');
        if (all) all.checked = n > 0 && n === checks().length;
    }
    document.getElementById('dpCheckAll')?.addEventListener('change', function () {
        checks().forEach(c => { c.checked = this.checked; });
        refreshBulk();
    });
    document.addEventListener('change', e => { if (e.target.classList?.contains('dp-check')) refreshBulk(); });
    refreshBulk();

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-dp-action]');
        if (!btn) return;
        e.preventDefault();

        mode = btn.dataset.dpAction;
        const scope = btn.dataset.dpScope;
        let ids = [];
        if (scope === 'one') { ids = [btn.dataset.dpId]; who = `<b>${esc(btn.dataset.dpName)}</b>`; }
        else if (scope === 'selected') { ids = selected(); who = `<b>${ids.length}</b> selected ${LABEL}(s)`; }
        else { who = `<b>all</b> ${LABEL}s`; }
        if (scope !== 'all' && !ids.length) return;

        document.getElementById('dpScope').value = scope === 'all' ? 'all' : 'selected';
        document.getElementById('dpIds').innerHTML = ids.map(id => `<input type="hidden" name="ids[]" value="${esc(id)}">`).join('');
        form.action = mode === 'remove' ? form.dataset.remove : form.dataset.push;

        const remove = mode === 'remove';
        document.getElementById('dpTitle').innerHTML = remove
            ? '<i class="fas fa-user-slash mr-2"></i>Remove from Device'
            : '<i class="fas fa-upload mr-2"></i>Push to Device';
        document.getElementById('dpHeader').style.background = remove
            ? 'linear-gradient(90deg,#dc2626,#ef4444)' : 'linear-gradient(90deg,#4f46e5,#7c3aed)';
        document.getElementById('dpSummary').innerHTML = (remove ? 'Remove ' : 'Push ') + who + (remove ? ' from:' : ' to:');
        document.getElementById('dpHelpPush').classList.toggle('d-none', remove);
        document.getElementById('dpHelpRemove').classList.toggle('d-none', !remove);
        const sub = document.getElementById('dpSubmit');
        sub.className = 'btn ' + (remove ? 'btn-danger' : 'btn-primary');
        sub.innerHTML = remove ? '<i class="fas fa-user-slash mr-1"></i> Remove' : '<i class="fas fa-upload mr-1"></i> Push';
        document.getElementById('dpDevice').value = '';

        $('#devicePushModal').modal('show');
    });

    form.addEventListener('submit', function (e) {
        if (mode !== 'remove' || form.dataset.confirmed === '1') return;
        e.preventDefault();
        const dev = document.getElementById('dpDevice');
        const target = dev.value ? dev.options[dev.selectedIndex].text : 'ALL active devices';
        const go = () => { form.dataset.confirmed = '1'; form.submit(); };
        const msg = `Remove ${who.replace(/<[^>]+>/g, '')} from ${target}? Their fingerprints on the device will be deleted too.`;
        if (window.Swal) {
            Swal.fire({ title: 'Remove from device?', text: msg, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Yes, remove' })
                .then(r => { if (r.isConfirmed) go(); });
        } else if (confirm(msg)) {
            go();
        }
    });
    $('#devicePushModal').on('hidden.bs.modal', () => { delete form.dataset.confirmed; });
})();
</script>
@endpush
