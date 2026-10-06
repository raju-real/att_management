<?php
namespace App\Http\Controllers;

use App\Services\AdmsService;
use Illuminate\Http\Request;

class ZktecoAdmsController extends Controller
{
    private $adms;

    public function __construct(AdmsService $adms)
    {
        $this->adms = $adms;
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

    public function getRequest(Request $request) {
        return response(
            $this->adms->getRequest($request),
            200
        )->header(
            'Content-Type',
            'text/plain'
        );
    }

    public function deviceCommand(Request $request) {
        return response(
            $this->adms->deviceCommand($request),
            200
        )->header(
            'Content-Type',
            'text/plain'
        );
    }
}
