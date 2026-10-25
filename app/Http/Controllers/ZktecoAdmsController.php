<?php
namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Services\AdmsCommandService;
use App\Services\AdmsService;
use App\Services\DeviceUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoAdmsController extends Controller
{
    private $adms;
    private $commands;
    private $users;

    public function __construct(AdmsService $adms, AdmsCommandService $commands, DeviceUserService $users)
    {
        $this->adms     = $adms;
        $this->commands = $commands;
        $this->users    = $users;
    }

    public function cdata(Request $request)
    {
        if (! $this->registered($request)) {
            return $this->refuse($request, $request->isMethod('get') ? 'HANDSHAKE' : 'DATA ' . strtoupper((string) $request->query('table', '')));
        }

        // GET = handshake
        if ($request->isMethod('get')) {
            $this->track($request, 'HANDSHAKE');
            return response(
                $this->adms->handshake($request),
                200
            )->header(
                'Content-Type',
                'text/plain'
            );
        }
        // POST = device data
        $table = $request->query('table','');

        // Highest id before processing → rows above it were saved by this request.
        $lastId = (int) AttendanceLog::max('id');

        $body = $this->adms->processData(
            $request,
            $table
        );

        $extra = [];
        if (strtoupper($table) === 'ATTLOG') {
            $lines = array_filter(preg_split('/\r\n|\n|\r/', trim($request->getContent())), 'strlen');
            $saved = AttendanceLog::where('id', '>', $lastId)
                ->where('device_serial', (string) $request->query('SN'))
                ->count();
            $extra = ['received' => count($lines), 'saved' => $saved, 'duplicates' => count($lines) - $saved];
        }

        // Enrolled-user upload (answer to "Fetch Users" / DATA QUERY USERINFO,
        // or sent by the device on its own after enrolment changes).
        if (in_array(strtoupper($table), DeviceUserService::UPLOAD_TABLES, true)) {
            $device = $this->device($request);
            if ($device) {
                $extra['users_saved']      = $this->users->storeFromUpload($device, $request->getContent());
                $extra['teachers_created'] = $this->users->lastTeachersCreated;
            }
        }
        $this->track($request, 'DATA ' . strtoupper($table), $extra);

        return response(
            $body, 200
        )->header(
            'Content-Type',
            'text/plain'
        );
    }

    /**
     * Device poll. AdmsService still handles registration / last-seen; any
     * queued commands (e.g. a date-range attendance pull) are handed over
     * instead of the plain "OK".
     */
    public function getRequest(Request $request) {
        if (! $this->registered($request)) {
            return $this->refuse($request, null); // polls come every few seconds: not logged
        }
        $body    = $this->adms->getRequest($request);
        $pending = $this->commands->deliver($this->device($request));

        if ($pending !== null) {
            $this->track($request, 'COMMANDS SENT', ['commands' => trim($pending)]);
        }

        return response(
            $pending ?? $body,
            200
        )->header(
            'Content-Type',
            'text/plain'
        );
    }

    /**
     * Device reports command results; mark them done/failed, then let
     * AdmsService do its usual logging.
     */
    public function deviceCommand(Request $request) {
        if (! $this->registered($request)) {
            return $this->refuse($request, 'COMMAND RESULT');
        }
        $body = $this->adms->deviceCommand($request);
        $this->commands->applyResults($this->device($request), $request->getContent());
        $this->track($request, 'COMMAND RESULT', ['result' => trim($request->getContent())]);

        return response(
            $body,
            200
        )->header(
            'Content-Type',
            'text/plain'
        );
    }

    /**
     * Only devices added in Configuration → Devices, ACTIVE and assigned to
     * an institute, may talk to the server. Unknown serials are no longer
     * auto-registered (AdmsService::registerDevice is simply never reached)
     * and their punches are not saved. Uses the device_serial_number cache.
     */
    private function registered(Request $request): bool
    {
        $sn = (string) $request->query('SN', '');
        return $sn !== '' && in_array($sn, activeDeviceSerials(), true);
    }

    private function refuse(Request $request, ?string $event)
    {
        if ($event !== null) {
            $this->track($request, 'REFUSED ' . $event, ['reason' => 'device not registered, inactive or without institute']);
        }
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    private function device(Request $request): ?Device
    {
        $sn = $request->query('SN');
        return $sn ? Device::where('serial_no', $sn)->first() : null;
    }

    /**
     * One line per device request in storage/logs/adms-YYYY-MM-DD.log.
     * Heartbeat polls with nothing to deliver are not logged (they come every
     * few seconds); the device's last_seen_at shows those.
     */
    private function track(Request $request, string $event, array $extra = []): void
    {
        try {
            Log::channel('adms')->info($event, array_merge([
                'sn' => $request->query('SN'),
                'ip' => $request->ip(),
            ], $extra));
        } catch (\Throwable) {
            // Logging must never break the device protocol.
        }
    }
}
