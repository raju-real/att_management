<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Device;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
use App\Models\DeviceEmployee;
use App\Http\Controllers\Controller;

class IClockController extends Controller
{
    // /iclock/getrequest => handshake / device registration
    public function handshake(Request $request)
    {
        $sn = $request->query('serial_no') ?? $request->input('serial_no');
        if ($sn) {
            Device::updateOrCreate(
                ['serial_no' => $sn],
                ['ip' => $request->ip(), 'last_seen_at' => now()]
            );
        }
        // You can also parse query string/body to detect command responses and mark DeviceCommand 'done'.
        return response('OK', 200);
    }

    // /iclock/cdata => device pushes attendance lines (ATTLOG,...)
    public function capture(Request $request)
    {
        $sn = $request->query('serial_no') ?? $request->input('serial_no');
        $device = Device::where('serial_no', $sn)->first();
        $raw = trim($request->getContent() ?? '');
        if ($raw === '') return response('OK:0', 200);

        $lines = preg_split("/\r\n|\n|\r/", $raw);
        $count = 0;
        foreach ($lines as $line) {
            if (!str_starts_with($line, 'ATTLOG')) continue;
            // ATTLOG,UserID,VerifyMode,YYYY-MM-DD HH:MM:SS,WorkCode
            [$tag, $userId, $verify, $dateTime, $workCode] = array_pad(explode(',', $line), 5, null);
            $employee = User::where('device_user_id', $userId)->first();
            AttendanceLog::firstOrCreate(
                ['employee_id' => $employee->id, 'device_id' => $device->id, 'punch_time' => Carbon::parse($dateTime)],
                [
                    'user_id' => $userId,
                    'type' => 'fingerprint',
                    'direction' => 'in',
                    'verify_mode' => $verify ?? null,
                    'raw_payload' => ['line' => $line, 'work_code' => $workCode]
                ]
            );
            $count++;
        }
        return response("OK:{$count}", 200);
    }

    // /iclock/devicecmd => device polls to fetch pending commands
    public function commands(Request $request)
    {
        $sn = $request->query('serial_no') ?? $request->input('serial_no');
        $device = Device::where('serial_no', $sn)->first();
        if (!$device) return response('', 200);

        // Priority: device_employee pivot pending actions
        $pivot = DeviceEmployee::where('device_id', $device->id)
            ->whereIn('status', ['add_pending', 'delete_pending'])
            ->orderBy('id')->first();

        if ($pivot) {
            $employee = User::find($pivot->employee_id);
            if ($pivot->status === 'delete_pending') {
                $cmd = "DATA DELETE USERINFO PIN={$employee->device_user_id}";
            } else {
                $name = addcslashes($employee->name, "\\,");
                $card = $employee->card_no ?: '';
                $cmd = "DATA UPDATE USERINFO PIN={$employee->device_user_id} Name={$name} Card={$card} Privilege=0";
            }
            // mark pivot as 'synced' (or leave until device confirms)
            $pivot->update(['status' => 'synced', 'synced_at' => now()]);
            DeviceCommand::create(['device_id' => $device->id, 'command_text' => $cmd]);
        }

        $command = DeviceCommand::where('device_id', $device->id)->where('status', 'pending')->orderBy('id')->first();
        if ($command) {
            $command->update(['status' => 'sent', 'executed_at' => now()]);
            return response($command->command_text, 200);
        }
        return response('', 200);
    }

    // Add this method to IClockController for testing
    public function testView()
    {
        $employees = User::where('role', 'employee')->get();

        $recentEntries = AttendanceLog::with(['employee', 'device'])
            ->where('raw_payload->test_mode', true)
            ->orderBy('punch_time', 'desc')
            ->limit(10)
            ->get();

        return view('attendance.test', compact('employees', 'recentEntries'));
    }

    public function testAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,employee_id',
            'punch_time' => 'sometimes|date',
            'attendance_by' => 'sometimes|in:fingerprint,card,face,pin',
            'direction' => 'sometimes|in:in,out',
        ]);

        $employee = User::whereEmployeeId($request->employee_id)->firstOrFail();
        $punchTime = now();
        $formattedTime = Carbon::parse($punchTime)->format('Y-m-d H:i:s');
        $attdendanceLog = [
            'employee_id' => $employee->employee_id,
            'device_id' => $request->device_id,
            'user_id' => $employee->id,
            'punch_time' => $punchTime,
            'attendance_by' => $request->attendance_by ?? 'fingerprint',
            'verify_mode' => 'test',
            'client_ip' => $request->ip(),
            'created_by' => auth()->id(),
            'raw_payload' => json_encode([
                'line' => "ATTLOG,{$employee->device_user_id},0,{$formattedTime},TEST",
                'work_code' => 'TEST',
                'test_mode' => true
            ])
        ];
        //return $attdendanceLog;

        $attendanceLog = AttendanceLog::create($attdendanceLog);

        return response()->json([
            'success' => true,
            'message' => 'Test attendance recorded successfully',
            'data' => $attendanceLog
        ]);
    }
}
