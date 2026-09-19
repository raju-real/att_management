<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Device;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\DeviceCommand;
use Illuminate\Http\Response;

/**
 * IClockController — ZkTeco iClock / ADMS HTTP Push Protocol
 *
 * ZkTeco devices support an HTTP "Cloud Server" / ADMS feature where the
 * device initiates all communication to the server. This eliminates the need
 * for direct UDP socket access from the server to the device, making it work
 * on shared hosting and across subnets.
 *
 * Device configuration (MENU → COMM → Cloud Server / ADMS):
 *   Server Address : your-domain.com  (or public IP)
 *   Server Port    : 80 (HTTP) or 443 (HTTPS)
 *   HTTPS          : OFF (unless you have SSL configured)
 *   Enable         : ON
 *
 * Protocol flow:
 *   1. Device boots → GET  /iclock/getrequest?serial_no=SERIALNO  (handshake)
 *   2. Device polls  → GET  /iclock/getrequest?serial_no=SERIALNO  (get commands)
 *   3. Device pushes → POST /iclock/cdata?serial_no=SERIALNO       (attendance/user data)
 *   4. Server sends  → GET  /iclock/getrequest response body       (command text)
 */
class IClockController extends Controller
{
    // ─── Handshake / Command Delivery ─────────────────────────────────────────

    /**
     * GET /iclock/getrequest
     *
     * Two purposes:
     *  a) Device registration / heartbeat (updates last_seen_at)
     *  b) Return pending commands to the device
     */
    public function getRequest(Request $request): Response
    {
        $sn = $this->serialNo($request);

        // ── Debug log (remove after confirming device connects) ──────────────
        \Log::info('[iClock] getRequest called', [
            'sn'       => $sn,
            'all_params' => $request->all(),
            'query_string' => $request->server('QUERY_STRING'),
            'device_ip'  => $request->ip(),
            'url'        => $request->fullUrl(),
        ]);

        // Register or update the device's last-seen timestamp and IP
        if ($sn) {
            Device::where('serial_no', $sn)->update([
                'last_seen_at' => now(),
            ]);
        }

        // Fetch the oldest pending command for this device
        if ($sn) {
            $device = Device::where('serial_no', $sn)->first();
            if ($device) {
                $command = DeviceCommand::where('device_id', $device->id)
                    ->pending()
                    ->orderBy('id')
                    ->first();

                if ($command) {
                    $command->update(['status' => 'sent', 'executed_at' => now()]);
                    // iClock protocol: respond with the command text
                    return response($command->command_text, 200)
                        ->header('Content-Type', 'text/plain');
                }
            }
        }

        // No pending commands — tell device to check in again after 30 seconds
        return response("OK\r\nDelay:30\r\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    // ─── Data Capture (Attendance / User Push) ─────────────────────────────────

    /**
     * POST /iclock/cdata
     *
     * Device pushes attendance log lines and user info.
     * Body format (ATTLOG line):
     *   ATTLOG\t{UserPin}\t{VerifyMode}\t{DateTime}\t{Status}\t{WorkCode}\r\n
     *
     * Example:
     *   ATTLOG	1001	1	2026-09-19 08:30:00	0	0
     */
    public function capture(Request $request): Response
    {
        $sn = $this->serialNo($request);
        $device = $sn ? Device::where('serial_no', $sn)->first() : null;

        // Update heartbeat
        if ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        $raw = trim($request->getContent() ?? '');
        if ($raw === '') {
            return response("OK:0\r\n", 200)->header('Content-Type', 'text/plain');
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // ── ATTLOG lines ─────────────────────────────────────────────────
            if (str_starts_with($line, 'ATTLOG')) {
                $this->processAttLog($line, $device);
                $count++;
                continue;
            }

            // ── OPERLOG lines (user management confirmations) ─────────────
            // We can mark corresponding DeviceCommands as 'done' here if needed
        }

        return response("OK:{$count}\r\n", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * GET /iclock/devicecmd
     *
     * Some firmware variants use this endpoint instead of /iclock/getrequest
     * for command delivery. Proxies to getRequest for compatibility.
     */
    public function deviceCmd(Request $request): Response
    {
        return $this->getRequest($request);
    }

    // ─── ATTLOG Parser ─────────────────────────────────────────────────────────

    /**
     * Parse and persist a single ATTLOG line from the device.
     *
     * Format: ATTLOG\t{Pin}\t{VerifyMode}\t{YYYY-MM-DD HH:MM:SS}\t{Status}\t{WorkCode}
     *
     * We try to match the pin to a Student or Teacher first.
     * If neither found, we store as-is for admin review.
     */
    protected function processAttLog(string $line, ?Device $device): void
    {
        // Tab-delimited (some firmware uses comma — handle both)
        $parts = preg_split('/[\t,]/', $line);

        // Index: 0=ATTLOG, 1=pin, 2=verifyMode, 3=dateTime, 4=status, 5=workCode
        if (count($parts) < 4) return;

        $pin        = trim($parts[1] ?? '');
        $verifyMode = trim($parts[2] ?? '');
        $rawDate    = trim($parts[3] ?? '');
        $status     = trim($parts[4] ?? '0');
        $workCode   = trim($parts[5] ?? '');

        if (empty($pin) || empty($rawDate)) return;

        try {
            $punchTime = Carbon::parse($rawDate);
        } catch (\Throwable) {
            return;
        }

        // ── Identify user type ────────────────────────────────────────────
        $studentNo = null;
        $teacherNo = null;
        $userType  = null;
        $name      = null;

        // Check if pin matches a student
        $student = Student::where('student_no', $pin)
            ->orWhere('id', $pin)
            ->first();

        if ($student) {
            $studentNo = $student->student_no ?? (string) $student->id;
            $userType  = 'student';
            $name      = $student->name ?? null;
        }

        // If not a student, check teacher
        if (! $student) {
            $teacher = Teacher::where('teacher_no', $pin)
                ->orWhere('id', $pin)
                ->first();

            if ($teacher) {
                $teacherNo = $teacher->teacher_no ?? (string) $teacher->id;
                $userType  = 'teacher';
                $name      = $teacher->name ?? null;
            }
        }

        // If device_for is set, respect it
        if ($device && $device->device_for === 'student') {
            $teacherNo = null;
            $userType  = $studentNo ? 'student' : null;
        } elseif ($device && $device->device_for === 'teacher') {
            $studentNo = null;
            $userType  = $teacherNo ? 'teacher' : null;
        }

        // ── Map punch type ────────────────────────────────────────────────
        $punchType = match ((int) $status) {
            0       => 'IN',
            1       => 'OUT',
            default => 'UNKNOWN',
        };

        // ── Persist ───────────────────────────────────────────────────────
        // Use firstOrCreate to avoid duplicates
        $criteria = [
            'device_serial' => $device?->serial_no ?? 'PUSH',
            'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
        ];

        // Add unique-identifying key depending on user type
        if ($studentNo) {
            $criteria['student_no'] = $studentNo;
        } elseif ($teacherNo) {
            $criteria['teacher_no'] = $teacherNo;
        } else {
            // Unknown user — store with raw pin so admin can review
            $criteria['student_no'] = $pin; // Fallback
        }

        AttendanceLog::firstOrCreate($criteria, [
            'user_type'     => $userType,
            'student_no'    => $studentNo,
            'teacher_no'    => $teacherNo,
            'name'          => $name,
            'device_id'     => $device?->id,
            'device_serial' => $device?->serial_no ?? 'PUSH',
            'punch_time'    => $punchTime->format('Y-m-d H:i:s'),
            'attendance_by' => 'fingerprint',
            'verify_mode'   => $verifyMode,
            'work_code'     => $workCode,
            'punch_type'    => $punchType,
            'raw_payload'   => json_encode([
                'line'        => $line,
                'pin'         => $pin,
                'verify_mode' => $verifyMode,
                'status'      => $status,
                'work_code'   => $workCode,
            ]),
        ]);
    }

    // ─── Helper ────────────────────────────────────────────────────────────────

    protected function serialNo(Request $request): ?string
    {
        $sn = $request->query('serial_no')
            ?? $request->query('SN')
            ?? $request->input('serial_no')
            ?? $request->input('SN');

        return $sn ? trim($sn) : null;
    }
}
