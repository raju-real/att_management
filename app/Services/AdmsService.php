<?php

namespace App\Services;

use App\Models\AttendanceLog as ZktecoAttendance;
use App\Models\Device as ZktecoDevice;
// use App\Models\ZktecoAttendance;
// use App\Models\ZktecoDevice;
use App\Models\ZktecoUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdmsService
{
    /*
    |--------------------------------------------------------------------------
    | Register / Update Device
    |--------------------------------------------------------------------------
    */

    public function registerDevice(Request $request): ZktecoDevice
    {
        $serial = $request->query('SN');

        if (!$serial) {
            abort(400, 'Missing SN');
        }

        $device = ZktecoDevice::updateOrCreate(
            [
                'serial_number' => $serial
            ],
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_seen_at' => now(),
                'is_active' => true,
            ]
        );

        return $device;
    }


    /*
    |--------------------------------------------------------------------------
    | Handshake
    |--------------------------------------------------------------------------
    */

    public function handshake(Request $request): string
    {
        $device = $this->registerDevice($request);

        $serial = $device->serial_number;

        /*
         * ATTLOGStamp is important.
         *
         * 0 means device should send attendance records.
         *
         * Realtime=1 requests real-time attendance transmission.
         */

        return implode("\r\n", [
            "GET OPTION FROM: {$serial}",
            "Stamp=9999",
            "ATTLOGStamp=0",
            "OPERLOGStamp=0",
            "TransTimes=00:00;23:59",
            "TransInterval=1",
            "TransFlag=TransData AttLog OpLog EnrollUser ChgUser",
            "TimeZone=6",
            "Realtime=1",
            "Encrypt=None",
            "",
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Process cdata
    |--------------------------------------------------------------------------
    */

    public function processData(
        Request $request,
        string $table
    ): string {

        $device = $this->registerDevice($request);

        $body = $request->getContent();

        if (!$body) {
            return "OK";
        }

        switch (strtoupper($table)) {

            case 'ATTLOG':

                $this->processAttendance(
                    $device,
                    $body
                );

                break;


            case 'OPERLOG':

                $this->processOperationLog(
                    $device,
                    $body
                );

                break;


            case 'USER':

                $this->processUsers(
                    $device,
                    $body
                );

                break;


            default:

                Log::info(
                    'Unknown ZKTeco table',
                    [
                        'table' => $table,
                        'serial' => $device->serial_number,
                        'body' => $body,
                    ]
                );

                break;
        }

        return "OK";
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    private function processAttendance(
        ZktecoDevice $device,
        string $body
    ): void {

        $lines = preg_split(
            "/\r\n|\n|\r/",
            trim($body)
        );

        foreach ($lines as $line) {

            if (trim($line) === '') {
                continue;
            }

            /*
             * Standard ADMS ATTLOG is tab separated.
             *
             * PIN
             * DateTime
             * Status
             * VerifyType
             * WorkCode
             */

            $fields = preg_split(
                "/\t+/",
                trim($line)
            );

            if (count($fields) < 2) {

                Log::warning(
                    'Invalid ZKTeco ATTLOG',
                    [
                        'serial' => $device->serial_number,
                        'line' => $line,
                    ]
                );

                continue;
            }

            $pin = trim($fields[0]);

            $dateTime = trim($fields[1]);

            $status = isset($fields[2])
                ? (int) $fields[2]
                : 0;

            $verifyType = isset($fields[3])
                ? (int) $fields[3]
                : null;

            $workCode = isset($fields[4])
                ? trim($fields[4])
                : null;


            /*
             * Convert device time to application time.
             *
             * K40/K50 is commonly configured for Bangladesh.
             */

            try {

                $attendanceTime = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $dateTime,
                    $device->timezone ?: 'Asia/Dhaka'
                );

            } catch (\Throwable $e) {

                Log::warning(
                    'Invalid attendance date',
                    [
                        'serial' => $device->serial_number,
                        'date' => $dateTime,
                    ]
                );

                continue;
            }


            /*
             * Duplicate protection.
             */
            ZktecoAttendance::firstOrCreate(
                [
                    'device_id' => $device->id,
                    'pin' => $pin,
                    'attendance_time' => $attendanceTime,
                    'status' => $status,
                    'verify_type' => $verifyType,
                ],
                [
                    'serial_number' => $device->serial_number,
                    'work_code' => $workCode,
                    'raw_data' => $line,
                ]
            );
        }


        $device->update([
            'last_seen_at' => now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | User Data
    |--------------------------------------------------------------------------
    */

    private function processUsers(
        ZktecoDevice $device,
        string $body
    ): void {

        $lines = preg_split(
            "/\r\n|\n|\r/",
            trim($body)
        );

        foreach ($lines as $line) {

            if (trim($line) === '') {
                continue;
            }

            /*
             * Example:
             *
             * PIN=1001 Name=John Doe Privilege=0 Card=123456
             */

            $data = $this->parseKeyValueLine($line);

            if (!isset($data['PIN'])) {
                continue;
            }

            ZktecoUser::updateOrCreate(
                [
                    'device_id' => $device->id,
                    'pin' => $data['PIN'],
                ],
                [
                    'name' => $data['Name'] ?? null,
                    'privilege' => isset($data['Privilege'])
                        ? (int) $data['Privilege']
                        : null,
                    'card' => $data['Card'] ?? null,
                    'password' => $data['Password'] ?? null,
                    'group' => $data['Grp'] ?? null,
                    'timezone' => $data['TZ'] ?? null,
                ]
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Operation Log
    |--------------------------------------------------------------------------
    */

    private function processOperationLog(
        ZktecoDevice $device,
        string $body
    ): void {

        Log::info(
            'ZKTeco OPERLOG',
            [
                'serial' => $device->serial_number,
                'body' => $body,
            ]
        );

        $device->update([
            'last_seen_at' => now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Key=value parser
    |--------------------------------------------------------------------------
    */

    private function parseKeyValueLine(
        string $line
    ): array {

        $result = [];

        preg_match_all(
            '/(\w+)=("[^"]*"|\S+)/',
            $line,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {

            $key = $match[1];

            $value = trim(
                $match[2],
                '"'
            );

            $result[$key] = $value;
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Device Command Poll
    |--------------------------------------------------------------------------
    */

    public function getRequest(
        Request $request
    ): string {

        $device = $this->registerDevice($request);

        $device->update([
            'last_seen_at' => now(),
        ]);

        /*
         * For initial implementation:
         *
         * No pending command.
         */

        return "OK";
    }


    /*
    |--------------------------------------------------------------------------
    | Device Command Result
    |--------------------------------------------------------------------------
    */

    public function deviceCommand(
        Request $request
    ): string {

        $device = $this->registerDevice($request);

        $device->update([
            'last_seen_at' => now(),
        ]);

        Log::info(
            'ZKTeco command result',
            [
                'serial' => $device->serial_number,
                'body' => $request->getContent(),
                'query' => $request->query(),
            ]
        );

        return "OK";
    }
}
