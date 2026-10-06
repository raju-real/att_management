<?php
namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\AdmsCommandService;
use App\Services\AdmsService;
use Illuminate\Http\Request;

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

        return response(
            $this->adms->processData(
                $request,
                $table
            ), 200
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
}
