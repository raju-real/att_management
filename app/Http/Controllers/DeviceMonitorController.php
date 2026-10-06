<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\AttendanceReportService as Report;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Live check that push (ADMS) is working: is each device checking in, and
 * are punches arriving and being saved?
 */
class DeviceMonitorController extends Controller
{
    /** A push device polls every few seconds; silent this long = offline. */
    public const ONLINE_SECONDS = 120;

    public function index()
    {
        return view('configuration.device_monitor');
    }

    /** GET /devices-monitor/data — polled by the monitor page every 10s. */
    public function data()
    {
        $today = Carbon::today();

        $devices = Device::orderBy('name')->get()->map(function (Device $d) use ($today) {
            $last = DB::table('attendance_logs')->where('device_serial', $d->serial_no)->orderByDesc('id')->first(['punch_time', 'created_at']);
            $seen = $d->last_seen_at ? Carbon::parse($d->last_seen_at) : null;

            $commands = DeviceCommand::where('device_id', $d->id)
                ->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');

            return [
                'name'          => $d->name,
                'serial_no'     => $d->serial_no,
                'ip'            => $d->ip_address,
                'mode'          => $d->use_push_mode ? 'Push (ADMS)' : 'TCP',
                'status'        => $d->status,
                'online'        => $seen && $seen->diffInSeconds(now()) <= self::ONLINE_SECONDS,
                'last_seen'     => $seen?->format('d M Y, h:i:s A'),
                'last_seen_ago' => $seen?->diffForHumans(),
                'last_received' => $last?->created_at ? Carbon::parse($last->created_at)->format('d M Y, h:i:s A') : null,
                'last_received_ago' => $last?->created_at ? Carbon::parse($last->created_at)->diffForHumans() : null,
                // punch_time is indexed; created_at is not (this runs every 10s).
                'today_punches' => DB::table('attendance_logs')->where('punch_time', '>=', $today)
                    ->where('device_serial', $d->serial_no)->count(),
                'commands'      => [
                    'pending' => (int) ($commands['pending'] ?? 0),
                    'sent'    => (int) ($commands['sent'] ?? 0),
                    'failed'  => (int) ($commands['failed'] ?? 0),
                ],
            ];
        });

        // Latest punches as saved by the server (newest first).
        $feed = DB::table('attendance_logs as al')
            ->leftJoin('teachers as t', 't.teacher_no', '=', DB::raw("NULLIF(al.teacher_no, '')"))
            ->leftJoin('students as s', 's.id', '=', DB::raw(
                "(SELECT s2.id FROM students s2 WHERE s2.student_no = NULLIF(al.student_no, '')
                  ORDER BY s2.deleted_at IS NULL DESC, s2.id DESC LIMIT 1)"
            ))
            ->leftJoin('devices as d', 'd.serial_no', '=', 'al.device_serial')
            ->orderByDesc('al.id')
            ->limit(50)
            ->get([
                'al.id', 'al.user_type', 'al.punch_time', 'al.created_at', 'al.device_serial',
                DB::raw("COALESCE(NULLIF(al.student_no, ''), NULLIF(al.teacher_no, '')) AS user_no"),
                DB::raw("COALESCE(NULLIF(t.name, ''), " . Report::STUDENT_NAME_SQL . ") AS name"),
                'd.name as device_name',
            ])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'user_type'   => $r->user_type,
                'user_no'     => $r->user_no,
                'name'        => $r->name,
                'device'      => $r->device_name ?: $r->device_serial,
                'punch_time'  => $r->punch_time ? Carbon::parse($r->punch_time)->format('d M Y, h:i:s A') : null,
                'received_at' => $r->created_at ? Carbon::parse($r->created_at)->format('d M Y, h:i:s A') : null,
                'delay'       => ($r->punch_time && $r->created_at)
                    ? Carbon::parse($r->punch_time)->diffForHumans(Carbon::parse($r->created_at), ['syntax' => Carbon::DIFF_ABSOLUTE, 'short' => true])
                    : null,
            ]);

        return response()->json([
            'server_time' => now()->format('d M Y, h:i:s A'),
            'devices'     => $devices,
            'feed'        => $feed,
            'log'         => $this->logTail(40),
        ]);
    }

    /** Last lines of today's device log (storage/logs/adms-YYYY-MM-DD.log). */
    protected function logTail(int $lines): array
    {
        $file = storage_path('logs/adms-' . now()->format('Y-m-d') . '.log');
        if (! is_file($file)) {
            return [];
        }
        $size = filesize($file);
        $fh = fopen($file, 'r');
        fseek($fh, max(0, $size - 20000)); // the end of the file is enough
        $chunk = stream_get_contents($fh);
        fclose($fh);

        $rows = array_values(array_filter(explode("\n", $chunk), 'strlen'));
        return array_reverse(array_slice($rows, -$lines));
    }
}
