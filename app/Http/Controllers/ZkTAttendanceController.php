<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Jmrashed\Zkteco\Lib\ZKTeco;
use Exception;

class ZkTAttendanceController extends Controller
{
    /**
     * Create ZKTeco instance by device serial
     */
    public function zkBySerial(string $serial): Zkteco
    {
        $device = Device::where('serial_no', $serial)->firstOrFail();
        $zk = new Zkteco($device->ip_address, 4370);
        // Optional (if comm key is set)
        if (!empty($device->comm_key)) {
            $zk->setCommKey($device->comm_key);
        }
        return $zk;
    }

    public function test(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);

            if (!$zk->connect()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unable to connect device',
                ], 500);
            }

            $data = [
                'serial_number' => $zk->serialNumber(),
                'device_time' => $zk->getTime(),
                'user_count' => count($zk->getUser()),
                'log_count' => count($zk->getAttendance()),
            ];
            if (method_exists($zk, 'platform')) {
                $data['platform'] = $zk->platform();
            }
            if (method_exists($zk, 'getFirmwareVersion')) {
                $data['firmware'] = $zk->getFirmwareVersion();
            }

            $zk->disconnect();

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Test device connection
     */
    public function testConnection(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);

            if ($zk->connect()) {
                $info = $zk->getDeviceInfo();
                $zk->disconnect();

                return response()->json([
                    'status' => true,
                    'message' => 'Device connected successfully',
                    'device_info' => $info,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Unable to connect device',
            ], 500);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all users from device
     */
    public function getUsers(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);
            $zk->connect();

            $users = $zk->getUser();
            $zk->disconnect();

            return response()->json([
                'status' => true,
                'data' => $users,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload (set) user to device
     */
//    public function uploadUser(Request $request, string $serial)
//    {
//        $request->validate([
//            'uid' => 'required|numeric',
//            'user_id' => 'required|string',
//            'name' => 'required|string',
//            'password' => 'nullable|string',
//            'role' => 'nullable|numeric', // 0 = user, 14 = admin
//        ]);
//
//        try {
//            $zk = $this->zkBySerial($serial);
//            $zk->connect();
//
//            $response = $zk->setUser(
//                $request->uid,
//                $request->user_id,
//                $request->name,
//                $request->password ?? '',
//                $request->role ?? 0
//            );
//
//            $zk->disconnect();
//
//            return response()->json([
//                'status' => true,
//                'message' => 'User uploaded successfully',
//                'response' => $response,
//            ]);
//
//        } catch (Exception $e) {
//            return response()->json([
//                'status' => false,
//                'error' => $e->getMessage(),
//            ], 500);
//        }
//    }

    public function uploadUser()
    {
//        $request->validate([
//            'uid' => 'required|numeric',
//            'user_id' => 'required|string',
//            'name' => 'required|string',
//            'password' => 'nullable|string',
//            'role' => 'nullable|numeric', // 0 = user, 14 = admin
//        ]);

        $user_id = request()->get('user_id');
        $serial_number = request()->get('serial_number');

        try {
            $zk = $this->zkBySerial($serial_number);
            $zk->connect();
            $user = User::findOrFail($user_id);
            $response = $zk->setUser(
                $user->employee_id,
                $user->employee_id,
                $user->name,
                $request->password ?? '',
                $request->role ?? 0
            );

            $zk->disconnect();

            return response()->json([
                'status' => true,
                'message' => 'User uploaded successfully',
                'response' => $response,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance logs from device
     */
    public function getAttendances(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);
            $zk->connect();

            $attendances = $zk->getAttendance();
            $zk->disconnect();

            return response()->json([
                'status' => true,
                'count' => count($attendances),
                'data' => $attendances,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear attendance logs from device (optional)
     */
    public function clearAttendance(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);
            $zk->connect();

            $zk->clearAttendance();
            $zk->disconnect();

            return response()->json([
                'status' => true,
                'message' => 'Attendance cleared successfully',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Other useful features
     */
    public function deviceExtras(string $serial)
    {
        try {
            $zk = $this->zkBySerial($serial);
            $zk->connect();

            $data = [
                'serial' => $zk->serialNumber(),
                'time' => $zk->getTime(),
                'platform' => $zk->platform(),
                'firmware' => $zk->firmwareVersion(),
                'attendance_count' => $zk->getAttendanceCount(),
                'user_count' => $zk->getUserCount(),
            ];

            $zk->disconnect();

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
