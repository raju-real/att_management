<div class="modal fade no-print" id="syncAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('attendance.sync.background') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(90deg,#059669,#10b981);color:#fff">
                    <h5 class="modal-title" id="syncModalLabel"><i class="fas fa-cloud-download-alt mr-2"></i>Pull Attendance from Device</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Select Device</label>
                        <select name="device_id" class="form-control">
                            <option value="">All Active Devices</option>
                            @foreach(\App\Models\Device::where('status', 'active')->get() as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }} ({{ $dev->serial_no }})
                                    — {{ $dev->use_push_mode ? 'Push Mode' : 'TCP' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">From Date</label>
                        <input type="text" name="sync_from_date" class="form-control flat_datepicker"
                            value="{{ \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">To Date</label>
                        <input type="text" name="sync_to_date" class="form-control flat_datepicker"
                            value="{{ \Carbon\Carbon::today()->toDateString() }}">
                    </div>
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Push Mode devices:</strong> shows the count of records already in the database.<br>
                        <strong>TCP devices:</strong> connects live and pulls logs from device memory.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-cloud-download-alt mr-1"></i> Pull Now
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
