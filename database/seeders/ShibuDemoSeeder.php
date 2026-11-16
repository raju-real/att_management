<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Demo data for "Shibu High School": shifts, departments, a device, teachers/staff
 * with photos, students, and attendance punches.
 *
 *   php artisan db:seed --class=ShibuDemoSeeder        → today's attendance
 *   php artisan demo:attendance                        → the same
 *   php artisan demo:attendance --date=2026-11-12      → that date
 *   php artisan demo:attendance --days=30              → today and the 29 working days before it
 *
 * Safe to run again: shifts, departments, people and the device are updated in place
 * (matched by name / ID / serial), and the demo punches of each seeded date are
 * replaced, never doubled. Only demo punches (from the demo device) are touched.
 */
class ShibuDemoSeeder extends Seeder
{
    public const INSTITUTE   = 'Shibu High School';
    public const DEVICE      = 'SHIBU-DEMO-01';
    public const PHOTO_DIR   = 'assets/files/images/teachers';

    /** Set by the demo:attendance command. */
    public static ?string $date = null;
    public static int $days = 1;

    private const SHIFTS = [
        'Morning Shift' => ['08:00:00', '14:00:00', '08:15:00'],
        'Day Shift'     => ['10:00:00', '16:00:00', '10:15:00'],
    ];

    private const DEPARTMENTS = [
        'Administration' => 'Morning Shift',
        'Science'        => 'Morning Shift',
        'Arts'           => 'Morning Shift',
        'Commerce'       => 'Day Shift',
        'Office Staff'   => 'Day Shift',
    ];

    /** teacher_no => [name, designation, department, gender] */
    private const PEOPLE = [
        '101' => ['Md. Abdul Karim',     'Head Teacher',                  'Administration', 'Male'],
        '102' => ['Nasrin Akter',        'Assistant Head Teacher',        'Administration', 'Female'],
        '103' => ['Rafiqul Islam',       'Senior Teacher (Physics)',      'Science',        'Male'],
        '104' => ['Sharmin Sultana',     'Assistant Teacher (Chemistry)', 'Science',        'Female'],
        '105' => ['Mahbub Alam',         'Assistant Teacher (Biology)',   'Science',        'Male'],
        '106' => ['Farhana Yasmin',      'Assistant Teacher (Math)',      'Science',        'Female'],
        '107' => ['Tanvir Hasan',        'Assistant Teacher (ICT)',       'Science',        'Male'],
        '108' => ['Kamrul Hasan',        'Senior Teacher (Bangla)',       'Arts',           'Male'],
        '109' => ['Taslima Begum',       'Assistant Teacher (English)',   'Arts',           'Female'],
        '110' => ['Jahid Hossain',       'Assistant Teacher (History)',   'Arts',           'Male'],
        '111' => ['Sumaiya Rahman',      'Assistant Teacher (Geography)', 'Arts',           'Female'],
        '112' => ['Rokeya Khatun',       'Assistant Teacher (Accounting)','Commerce',       'Female'],
        '113' => ['Shafiqul Islam',      'Assistant Teacher (Finance)',   'Commerce',       'Male'],
        '114' => ['Nusrat Jahan',        'Assistant Teacher (Business)',  'Commerce',       'Female'],
        '115' => ['Mizanur Rahman',      'Office Assistant',              'Office Staff',   'Male'],
        '116' => ['Selina Parvin',       'Computer Operator',             'Office Staff',   'Female'],
        '117' => ['Babul Mia',           'Security Guard',                'Office Staff',   'Male'],
        '118' => ['Jasim Uddin',         'Accountant',                    'Office Staff',   'Male'],
    ];

    private const STUDENTS = [
        ['Ayesha', 'Rahman', 'Six'], ['Tanvir', 'Ahmed', 'Six'], ['Nusrat', 'Jahan', 'Seven'], ['Sabbir', 'Hossain', 'Seven'],
        ['Mim', 'Akter', 'Eight'], ['Arif', 'Hasan', 'Eight'], ['Sumaiya', 'Islam', 'Nine'], ['Rakib', 'Hasan', 'Nine'],
        ['Jannatul', 'Ferdous', 'Ten'], ['Fahim', 'Chowdhury', 'Ten'],
    ];

    public function run(): void
    {
        $inst = DB::table('users')->where('role', 'institute')->where('name', self::INSTITUTE)->whereNull('deleted_at')->first();
        if (! $inst) {
            $this->say('error', 'Institute "' . self::INSTITUTE . '" not found. Create it first (Configuration → Institutes).');
            return;
        }
        $iid = (int) $inst->id;
        $now = now();

        DB::transaction(function () use ($iid, $now) {
            $shifts = $this->shifts($iid, $now);
            $deps   = $this->departments($iid, $shifts, $now);
            $device = $this->device($iid, $now);
            $this->teachers($iid, $deps, $device, $now);
            $this->students($iid, $now);
        });
        $this->say('info', 'Shifts, departments, device, ' . count(self::PEOPLE) . ' teachers/staff (with photos) and ' . count(self::STUDENTS) . ' students are ready.');

        $this->attendance($iid);
    }

    // ───────────────────────────── setup ─────────────────────────────

    private function shifts(int $iid, $now): array
    {
        $ids = [];
        foreach (self::SHIFTS as $title => [$in, $out, $late]) {
            $key = ['institute_id' => $iid, 'title' => $title];
            DB::table('shifts')->updateOrInsert($key, [
                'in_time' => $in, 'out_time' => $out, 'late_count_time' => $late, 'early_out_count_time' => null,
                'punch_before_minutes' => 60, 'punch_after_minutes' => 60, 'is_overnight' => 0, 'status' => 'active',
                'deleted_at' => null, 'updated_at' => $now,
            ]);
            DB::table('shifts')->where($key)->whereNull('created_at')->update(['created_at' => $now]);
            $ids[$title] = (int) DB::table('shifts')->where($key)->value('id');
        }
        return $ids;
    }

    private function departments(int $iid, array $shifts, $now): array
    {
        $ids = [];
        foreach (self::DEPARTMENTS as $name => $shift) {
            $key = ['institute_id' => $iid, 'name' => $name];
            DB::table('departments')->updateOrInsert($key, ['shift_id' => $shifts[$shift], 'status' => 'active', 'deleted_at' => null, 'updated_at' => $now]);
            DB::table('departments')->where($key)->whereNull('created_at')->update(['created_at' => $now]);
            $ids[$name] = (int) DB::table('departments')->where($key)->value('id');
        }
        return $ids;
    }

    private function device(int $iid, $now): int
    {
        $key = ['serial_no' => self::DEVICE];
        DB::table('devices')->updateOrInsert($key, [
            'institute_id' => $iid, 'name' => 'Main Gate (Demo)', 'slug' => 'main-gate-demo', 'ip_address' => '192.168.1.250',
            'device_port' => '4370', 'location_note' => 'Main gate', 'device_for' => 'student_teacher', 'status' => 'active',
            'use_push_mode' => 1, 'is_active' => 1, 'last_seen_at' => $now, 'created_by' => $iid, 'deleted_at' => null, 'updated_at' => $now,
        ]);
        DB::table('devices')->where($key)->whereNull('created_at')->update(['created_at' => $now]);
        return (int) DB::table('devices')->where($key)->value('id');
    }

    private function teachers(int $iid, array $deps, int $device, $now): void
    {
        File::ensureDirectoryExists(public_path(self::PHOTO_DIR));
        foreach (self::PEOPLE as $no => [$name, $des, $dep, $gender]) {
            $photo = self::PHOTO_DIR . "/demo-shibu-{$no}.png";
            $this->avatar(public_path($photo), (int) $no, $gender === 'Female');

            $key = ['institute_id' => $iid, 'teacher_no' => (string) $no];
            DB::table('teachers')->updateOrInsert($key, [
                'department_id' => $deps[$dep], 'name' => $name, 'designation' => $des, 'image' => $photo,
                'email' => strtolower(preg_replace('/[^a-z]+/i', '.', trim(str_replace('Md.', '', $name)))) . '@shibu.edu.bd',
                'mobile' => '017' . str_pad((string) (11000000 + (int) $no * 7919), 8, '0', STR_PAD_LEFT),
                'gender' => $gender, 'blood_group' => ['A+', 'B+', 'O+', 'AB+', 'A-', 'O-'][$no % 6], 'religion' => 'Islam',
                'subject' => preg_match('/\((.+)\)/', $des, $m) ? $m[1] : null,
                'joining_date' => (2008 + $no % 15) . '-01-' . str_pad((string) (1 + $no % 27), 2, '0', STR_PAD_LEFT),
                'joining_post' => $des, 'present_address' => 'Lichu Bagan, Baruahat', 'permanent_address' => 'Baruahat, Bangladesh',
                'deleted_at' => null, 'updated_at' => $now,
            ]);
            DB::table('teachers')->where($key)->whereNull('created_at')->update(['created_at' => $now]);

            DB::table('device_users')->updateOrInsert(['institute_id' => $iid, 'device_id' => $device, 'pin' => (string) $no],
                ['name' => $name, 'privilege' => 0, 'updated_at' => $now, 'created_at' => $now]);
        }
    }

    private function students(int $iid, $now): void
    {
        foreach (self::STUDENTS as $i => [$first, $last, $class]) {
            $key = ['institute_id' => $iid, 'student_no' => (string) (5001 + $i)];
            DB::table('students')->updateOrInsert($key, [
                'student_id' => 'SHS-' . (2026001 + $i), 'firstname' => $first, 'lastname' => $last, 'class' => $class,
                'roll' => (string) ($i % 2 + 1), 'section' => 'A', 'shift' => 'Morning', 'gender' => $i % 2 ? 'Male' : 'Female',
                'fname' => 'Md. ' . $last, 'mobile' => '019' . str_pad((string) (20000000 + $i * 4241), 8, '0', STR_PAD_LEFT),
                'status' => 'active', 'session' => '2026', 'deleted_at' => null, 'updated_at' => $now,
            ]);
            DB::table('students')->where($key)->whereNull('created_at')->update(['created_at' => $now]);
        }
    }

    // ───────────────────────────── attendance ─────────────────────────────

    private function attendance(int $iid): void
    {
        $last    = Carbon::parse(self::$date ?: today()->toDateString())->startOfDay();
        $weekly  = array_map('strtolower', weeklyHolidays($iid));
        $office  = officeHolidays($iid);
        $device  = (int) DB::table('devices')->where('serial_no', self::DEVICE)->value('id');
        $dates   = [];

        // the requested date always; earlier days only when they are working days
        for ($d = $last->copy(), $guard = 0; count($dates) < max(1, self::$days) && $guard < 400; $d->subDay(), $guard++) {
            $holiday = in_array(strtolower($d->format('l')), $weekly, true) || in_array($d->toDateString(), $office, true);
            if ($holiday && $d->ne($last)) {
                continue;
            }
            if ($holiday) {
                $this->say('warn', $d->format('d M Y, l') . ' is a holiday of this institute: punches are added, but reports show the day as Holiday.');
            }
            $dates[] = $d->copy();
        }

        $total = 0;
        foreach ($dates as $day) {
            $total += $this->punchesFor($iid, $device, $day);
        }
        $this->say('info', "Attendance: {$total} punches for " . count($dates) . ' day(s), ' . end($dates)->format('d M Y') . ' → ' . $last->format('d M Y') . '.');
    }

    /** Replaces the demo punches of one date. Returns how many were inserted. */
    private function punchesFor(int $iid, int $device, Carbon $day): int
    {
        DB::table('attendance_logs')->where('institute_id', $iid)->where('device_serial', self::DEVICE)
            ->whereBetween('punch_time', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->delete();

        mt_srand((int) $day->format('Ymd')); // the same date gives the same picture every run
        $now  = now();
        $rows = [];
        $log  = fn (string $type, string $no, Carbon $t, int $k) => [
            'institute_id' => $iid, 'user_type' => $type,
            'teacher_no' => $type === 'teacher' ? $no : null, 'student_no' => $type === 'student' ? $no : null,
            'device_id' => $device, 'device_serial' => self::DEVICE, 'pin' => $no,
            'punch_time' => $t, 'attendance_time' => $t, 'status' => $k, 'verify_type' => 1,
            'attendance_by' => $k ? 'face' : 'fingerprint', 'punch_type' => $k ? 'out' : 'in', 'created_at' => $t, 'updated_at' => $t,
        ];

        foreach (self::PEOPLE as $no => [, , $dep]) {
            $roll = mt_rand(1, 100);
            if ($roll <= 12) {
                continue;                                   // ~12% absent
            }
            [$in, $out] = self::SHIFTS[self::DEPARTMENTS[$dep]];
            $start = Carbon::parse($day->toDateString() . ' ' . $in);
            $end   = Carbon::parse($day->toDateString() . ' ' . $out);
            $inAt  = $roll > 80
                ? $start->copy()->addMinutes(mt_rand(17, 55))  // ~20% late
                : $start->copy()->subMinutes(mt_rand(3, 35));
            $outAt = $end->copy()->addMinutes(mt_rand(-20, 40));

            foreach ([$inAt, $outAt] as $k => $t) {
                if ($t->lte($now)) {                        // today: nothing in the future
                    $rows[] = $log('teacher', (string) $no, $t->copy()->addSeconds(mt_rand(0, 59)), $k);
                }
            }
        }
        foreach (self::STUDENTS as $i => $s) {
            if (mt_rand(1, 100) <= 10) {
                continue;
            }
            $t = Carbon::parse($day->toDateString() . ' 07:' . str_pad((string) mt_rand(25, 59), 2, '0', STR_PAD_LEFT));
            if ($t->lte($now)) {
                $rows[] = $log('student', (string) (5001 + $i), $t, 0);
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('attendance_logs')->insert($chunk);
        }
        return count($rows);
    }

    // ───────────────────────────── photo ─────────────────────────────

    /** A friendly flat portrait (head and shoulders), drawn once per person. */
    private function avatar(string $file, int $seed, bool $female): void
    {
        if (is_file($file)) {
            return;
        }
        $S  = 600; // drawn large, then scaled down for smooth edges
        $im = imagecreatetruecolor($S, $S);
        $c  = fn (array $rgb) => imagecolorallocate($im, ...$rgb);

        $bgs   = [[219, 234, 254], [220, 252, 231], [254, 243, 199], [237, 233, 254], [252, 231, 243], [224, 242, 254]];
        $skins = [[241, 194, 160], [224, 172, 135], [198, 140, 102], [176, 120, 86]];
        $wear  = [[30, 64, 175], [4, 120, 87], [126, 34, 206], [180, 83, 9], [190, 18, 60], [15, 118, 110], [55, 65, 81]];
        $bg    = $bgs[$seed % count($bgs)];
        $skin  = $skins[$seed % count($skins)];
        $cloth = $wear[($seed * 3) % count($wear)];
        $hair  = [[40, 30, 25], [25, 20, 20], [70, 45, 30]][$seed % 3];

        // background: soft vertical gradient
        for ($y = 0; $y < $S; $y++) {
            $k = $y / $S;
            imageline($im, 0, $y, $S, $y, $c([(int) ($bg[0] - 25 * $k), (int) ($bg[1] - 25 * $k), (int) ($bg[2] - 15 * $k)]));
        }
        $cx = 300;
        $skinC = $c($skin);
        $shade = $c([(int) ($skin[0] * .86), (int) ($skin[1] * .84), (int) ($skin[2] * .84)]);

        if ($female) {
            // hijab: wraps the head and falls over the shoulders
            $scarf = $c([(int) ($cloth[0] * .8 + 30), (int) ($cloth[1] * .8 + 30), (int) ($cloth[2] * .8 + 30)]);
            imagefilledellipse($im, $cx, 560, 520, 300, $c($cloth));
            imagefilledellipse($im, $cx, 260, 300, 340, $scarf);
            imagefilledpolygon($im, [150, 300, 450, 300, 470, 520, 130, 520], $scarf);
            imagefilledellipse($im, $cx, 270, 190, 230, $skinC);
        } else {
            // shirt + collar, neck, head, short hair
            imagefilledellipse($im, $cx, 580, 540, 320, $c($cloth));
            imagefilledpolygon($im, [250, 430, 300, 500, 350, 430], $c([245, 245, 245]));
            imagefilledrectangle($im, 260, 330, 340, 440, $shade);
            imagefilledellipse($im, $cx, 260, 210, 250, $skinC);
            imagefilledellipse($im, $cx, 190, 220, 130, $c($hair));
            imagefilledrectangle($im, 190, 190, 410, 215, $c($hair));
            imagefilledellipse($im, 196, 270, 30, 60, $skinC);
            imagefilledellipse($im, 404, 270, 30, 60, $skinC);
            if ($seed % 3 === 0) { // beard for some
                imagefilledarc($im, $cx, 300, 200, 170, 0, 180, $c($hair), IMG_ARC_PIE);
                imagefilledellipse($im, $cx, 318, 90, 40, $skinC);
            }
        }
        // face: eyes, brows, smile
        $ink = $c([40, 30, 30]);
        imagefilledellipse($im, 262, 262, 22, 24, $ink);
        imagefilledellipse($im, 338, 262, 22, 24, $ink);
        imagesetthickness($im, 6);
        imagearc($im, 262, 240, 44, 20, 200, 340, $ink);
        imagearc($im, 338, 240, 44, 20, 200, 340, $ink);
        imagearc($im, $cx, 318, 80, 46, 20, 160, $c([150, 60, 60]));
        imagefilledellipse($im, $cx, 292, 14, 20, $shade);

        $out = imagecreatetruecolor(300, 300);
        imagecopyresampled($out, $im, 0, 0, 0, 0, 300, 300, $S, $S);
        imagepng($out, $file);
        imagedestroy($im);
        imagedestroy($out);
    }

    private function say(string $level, string $text): void
    {
        if ($this->command) {
            $this->command->{$level === 'warn' ? 'warn' : ($level === 'error' ? 'error' : 'info')}($text);
        }
    }
}
