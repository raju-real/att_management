<?php
namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Services\AdmsCommandService;
use App\Services\AdmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZktecoAdmsController extends Controller
{
    private $adms;
    private $commands;

    public function __construct(AdmsService $adms, AdmsCommandService $commands)
    {
        $this->adms     = $adms;
        $this->commands = $commands;
    }

    public function cdata(Request $request)
    {
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
